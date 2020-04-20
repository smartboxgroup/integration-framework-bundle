<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Consumers;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\AsyncQueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueProtocol;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Exceptions\MessageDecodingFailedException;
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
        $serializer = $consumer->getSerializer();
        $queueDriver = $this->getQueueDriver('amqp');
        $queueDriver->connect();

        $message = $this->createMessage(new EntityX(333));
        $queueMessage = new QueueMessage();
        $queueMessage->setQueue(self::QUEUE);
        $queueMessage->setDestinationURI('direct://test');
        $queueMessage->setBody($message);

        $encodedMessage = $serializer->encode($queueMessage);
        $queueDriver->send($queueMessage->getQueue(), $encodedMessage['body'], $encodedMessage['headers']);

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
     * Check if decoding exception handler is triggered when consumers fails to deserialize a message. By default, the
     * ReThrowExceptionHandler is used which simply throws the exception again.
     */
    public function testUsesExceptionHandlerOnSerializationErrors()
    {
        $this->expectException(MessageDecodingFailedException::class);

        $consumer = $this->getConsumer();
        $queueDriver = $this->getQueueDriver('amqp');
        $queueDriver->connect();
        $queueDriver->send(self::QUEUE, '123', [QueueMessage::HEADER_FROM => 'direct://test']);

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
