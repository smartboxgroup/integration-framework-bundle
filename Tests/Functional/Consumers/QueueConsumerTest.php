<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Consumers;

use Psr\Log\NullLogger;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueConsumer;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueProtocol;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Tests\BaseKernelTestCase;
use Smartbox\Integration\FrameworkBundle\Tests\EntityX;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class QueueConsumerTest.
 */
class QueueConsumerTest extends BaseKernelTestCase
{
    const QUEUE = '/test/command';

    /**
     * @return QueueConsumer
     */
    private function getConsumer()
    {
        return $this->helper->getConsumer('queue');
    }

    /**
     * @param string $queueDriverName
     *
     * @return QueueDriverInterface
     */
    private function getQueueDriver($queueDriverName)
    {
        return $this->helper->getQueueDriver($queueDriverName);
    }

    /**
     * Stops the consumer if an endless loop is detected.
     */
    public function handleSignal()
    {
        $consumerDriver = $this->getConsumer();
        $consumerDriver->stop();
        $this->fail('The queue consumer seems to be in an endless loop, please check if you enabled the destinationPathSeparatorPlugin in ActiveMQ');
    }

    /**
     * Create 3 messages and send them the to queuing system to be consumed later.
     */
    public function testExecute()
    {
        $consumer = $this->getConsumer();
        $serializer = $consumer->getSerializer();
        $queueDriver = $this->getQueueDriver('main');
        $queueDriver->connect();

        $message1 = $this->createMessage(new EntityX(111));
        $queueMessage = $queueDriver->createQueueMessage();
        $queueMessage->setQueue(self::QUEUE);
        $queueMessage->setHeader(Message::HEADER_FROM, self::QUEUE);
        $queueMessage->setBody($message1);
        $queueMessage->setDestinationURI('direct://test');

        $encodedMessage = $serializer->encode($queueMessage);
        $queueDriver->send($queueMessage->getQueue(), $encodedMessage['body'], $encodedMessage['headers']);

        $message2 = $this->createMessage(new EntityX(222));
        $queueMessage = $queueDriver->createQueueMessage();
        $queueMessage->setQueue(self::QUEUE);
        $queueMessage->setHeader(Message::HEADER_FROM, self::QUEUE);
        $queueMessage->setBody($message2);
        $queueMessage->setDestinationURI('direct://test');

        $encodedMessage = $serializer->encode($queueMessage);
        $queueDriver->send($queueMessage->getQueue(), $encodedMessage['body'], $encodedMessage['headers']);

        $message3 = $this->createMessage(new EntityX(333));
        $queueMessage = $queueDriver->createQueueMessage();
        $queueMessage->setQueue(self::QUEUE);
        $queueMessage->setHeader(Message::HEADER_FROM, self::QUEUE);
        $queueMessage->setBody($message3);
        $queueMessage->setDestinationURI('direct://test');

        $encodedMessage = $serializer->encode($queueMessage);
        $queueDriver->send($queueMessage->getQueue(), $encodedMessage['body'], $encodedMessage['headers']);

        $messages = [$message1, $message2, $message3];
        $queues = [self::QUEUE];

        $handlerMock = $this->createMock(MessageHandler::class);
        $handlerMock->expects($this->exactly(3))->method('handle')
            ->with($this->callback(
                function ($message) use ($messages) {
                    $res = \array_search($message, $messages);
                    if (false !== $res) {
                        unset($messages[$res]);

                        return true;
                    }

                    return false;
                }
            ), $this->callback(
                function (Endpoint $endpoint) use ($queues) {
                    return true;
                }
            ))
            ->willReturn(true);

        $queueProtocol = new QueueProtocol(true, 3600);
        $optionsResolver = new OptionsResolver();
        $queueProtocol->configureOptionsResolver($optionsResolver);

        $opts = $optionsResolver->resolve([
            QueueProtocol::OPTION_QUEUE_DRIVER => 'main',
            QueueProtocol::OPTION_QUEUE_NAME => self::QUEUE,
        ]);

        $endpoint = new Endpoint('xxx', $opts, $queueProtocol, null, $consumer, $handlerMock);

        $consumer->setExpirationCount(3);   // This will make the consumer stop after reading 3 messages

        declare(ticks=1);
        \pcntl_signal(SIGALRM, [$this, 'handleSignal']);
        \pcntl_alarm(30);
        $consumer->consume($endpoint);
        \pcntl_alarm(0);

        $output = $this->getActualOutput();
        $this->assertNotContains('A message was consumed', $output); // The consumer should not display message information if no logger
    }

    /**
     * Test that when we use a logger, it is really used, and it does not display message according to the verbosity by using NullLogger.
     */
    public function testExecuteWithNullLogger()
    {
        $consumer = $this->getConsumer();
        $serializer = $this->getConsumer()->getSerializer();
        $queueDriver = $this->getQueueDriver('main');
        $queueDriver->connect();

        $message1 = $this->createMessage(new EntityX(111));
        $queueMessage = $queueDriver->createQueueMessage();
        $queueMessage->setQueue(self::QUEUE);
        $queueMessage->setHeader(Message::HEADER_FROM, self::QUEUE);
        $queueMessage->setBody($message1);
        $queueMessage->setDestinationURI('direct://test');

        $encodedMessage = $serializer->encode($queueMessage);
        $queueDriver->send($queueMessage->getQueue(), $encodedMessage['body'], $encodedMessage['headers']);

        $messages = [$message1];
        $queues = [self::QUEUE];

        $loggerMock = $this->createMock(NullLogger::class);
        $loggerMock->expects($this->atLeastOnce())->method('info'); // We test that the logger will be used.

        $handlerMock = $this->createMock(MessageHandler::class);
        $handlerMock->expects($this->exactly(1))->method('handle')
            ->with($this->callback(
                function ($message) use ($messages) {
                    $res = \array_search($message, $messages);
                    if (false !== $res) {
                        unset($messages[$res]);

                        return true;
                    }

                    return false;
                }
            ), $this->callback(
                function (Endpoint $endpoint) use ($queues) {
                    return true;
                }
            ))
            ->willReturn(true);

        $queueProtocol = new QueueProtocol(true, 3600);
        $optionsResolver = new OptionsResolver();
        $queueProtocol->configureOptionsResolver($optionsResolver);

        $opts = $optionsResolver->resolve([
            QueueProtocol::OPTION_QUEUE_DRIVER => 'main',
            QueueProtocol::OPTION_QUEUE_NAME => self::QUEUE,
        ]);

        $endpoint = new Endpoint('xxx', $opts, $queueProtocol, null, $consumer, $handlerMock);

        $consumer->setExpirationCount(1);   // This will make the consumer stop after reading 1 message

        declare(ticks=1);
        \pcntl_signal(SIGALRM, [$this, 'handleSignal']);
        \pcntl_alarm(30);
        $consumer->setLogger($loggerMock);
        $consumer->consume($endpoint);
        \pcntl_alarm(0);

        $output = $this->getActualOutput();
        $this->assertNotContains('A message was consumed', $output); // The consumer should not display message information with NullLogger
    }
}
