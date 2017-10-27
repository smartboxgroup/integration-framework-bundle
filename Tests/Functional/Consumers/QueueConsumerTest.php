<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Consumers;

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
 * Class QueueConsumerTest
 */
class QueueConsumerTest extends BaseKernelTestCase
{
    const queue = '/test/command';

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
     * Stops the consumer if an endless loop is detected
     */
    public function handleSignal()
    {
        $consumerDriver = $this->getConsumer();
        $consumerDriver->stop();
        $this->fail('The queue consumer seems to be in an endless loop, please check if you enabled the destinationPathSeparatorPlugin in ActiveMQ');
    }

    /**
     * Create 3 messages and send them the to queuing system to be consumed later
     */
    public function testExecute()
    {
        $consumer = $this->getConsumer();
        $queueDriver = $this->getQueueDriver('main');
        $queueDriver->connect();

        $message1 = $this->createMessage(new EntityX(111));
        $msg = $queueDriver->createQueueMessage();
        $msg->setQueue(self::queue);
        $msg->setHeader(Message::HEADER_FROM, self::queue);
        $msg->setBody($message1);
        $msg->setDestinationURI('direct://test');
        $queueDriver->send($msg);

        $message2 = $this->createMessage(new EntityX(222));
        $msg = $queueDriver->createQueueMessage();
        $msg->setQueue(self::queue);
        $msg->setHeader(Message::HEADER_FROM, self::queue);
        $msg->setBody($message2);
        $msg->setDestinationURI('direct://test');
        $queueDriver->send($msg);

        $message3 = $this->createMessage(new EntityX(333));
        $msg = $queueDriver->createQueueMessage();
        $msg->setQueue(self::queue);
        $msg->setHeader(Message::HEADER_FROM, self::queue);
        $msg->setBody($message3);
        $msg->setDestinationURI('direct://test');
        $queueDriver->send($msg);

        $messages = [$message1, $message2, $message3];
        $queues = [self::queue];

        $handlerMock = $this->createMock(MessageHandler::class);
        $handlerMock->expects($this->exactly(3))->method('handle')
            ->with($this->callback(
                function ($message) use ($messages) {
                    $res = array_search($message, $messages);
                    if ($res !== false) {
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
            QueueProtocol::OPTION_QUEUE_NAME => self::queue,
        ]);

        $endpoint = new Endpoint('xxx', $opts, $queueProtocol, null, $consumer, $handlerMock);

        $consumer->setExpirationCount(3);   // This will make the consumer stop after reading 3 messages

        declare(ticks=1);
        pcntl_signal(SIGALRM, [$this, 'handleSignal']);

        pcntl_alarm(30);
        $consumer->consume($endpoint);
        pcntl_alarm(0);
    }
}
