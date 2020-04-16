<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Consumers;

use JMS\Serializer\SerializerInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\AsyncQueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueProtocol;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Tests\BaseKernelTestCase;
use Smartbox\Integration\FrameworkBundle\Tests\EntityX;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AsyncQueueConsumerTest.
 */
class AsyncQueueConsumerTest extends BaseKernelTestCase
{
    const QUEUE = '/test/async';

    private function getConsumer()
    {
        return $this->helper->getConsumer('async_queue');
    }

    private function getQueueDriver(string $queueDriverName): AsyncQueueDriverInterface
    {
        return $this->helper->getQueueDriver($queueDriverName);
    }

    /**
     * Check if a message is consumed correctly from the broker and the consumer stops because of expiration count.
     */
    public function testConsume()
    {
        $consumer = $this->getConsumer();
        $queueDriver = $this->getQueueDriver('amqp');

        $message = $this->createMessage(new EntityX(333));
        $queueMessage = $queueDriver->createQueueMessage();
        $queueMessage->setQueue(self::QUEUE);
        $queueMessage->setDestinationURI('direct://test');
        $queueMessage->setBody($message);
        $queueDriver->connect();
        $queueDriver->send($queueMessage);

        $handlerMock = $this->createMock(MessageHandler::class);
        $handlerMock->expects($this->once())->method('handle');

        $queueProtocol = new QueueProtocol(true, 3600);
        $optionsResolver = new OptionsResolver();
        $queueProtocol->configureOptionsResolver($optionsResolver);

        $options = $optionsResolver->resolve([
            QueueProtocol::OPTION_QUEUE_DRIVER => 'amqp',
            QueueProtocol::OPTION_QUEUE_NAME => self::QUEUE,
        ]);

        $endpoint = new Endpoint('xxx', $options, $queueProtocol, null, $consumer, $handlerMock);

        $consumer->setExpirationCount(1);
        $consumer->consume($endpoint);

        $output = $this->getActualOutput();
        $this->assertNotContains('A message was consumed', $output);
    }

    /**
     * Callback should call the exception handler when it fails to deserialize a message. By default, the
     * ReThrowExceptionHandler is used which simply throws the exception again.
     */
    public function testUsesExceptionHandlerOnSerializationErrors()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('I cöuld nót dese�rialize that JSON strin��������');

        $consumer = $this->getConsumer();
        $queueDriver = $this->getQueueDriver('amqp');

        $message = $this->createMessage(new EntityX(666));
        $queueMessage = $queueDriver->createQueueMessage();
        $queueMessage->setQueue(self::QUEUE);
        $queueMessage->setDestinationURI('direct://test');
        $queueMessage->setBody($message);
        $queueDriver->connect();
        $queueDriver->send($queueMessage);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->willThrowException(new \RuntimeException('I cöuld nót dese�rialize that JSON strin��������'));

        $consumer->setSerializer($serializer);

        $queueProtocol = new QueueProtocol(true, 3600);
        $optionsResolver = new OptionsResolver();
        $queueProtocol->configureOptionsResolver($optionsResolver);

        $options = $optionsResolver->resolve([
            QueueProtocol::OPTION_QUEUE_DRIVER => 'amqp',
            QueueProtocol::OPTION_QUEUE_NAME => self::QUEUE,
        ]);

        $endpoint = new Endpoint('xxx', $options, $queueProtocol);

        $consumer->consume($endpoint);
    }
}
