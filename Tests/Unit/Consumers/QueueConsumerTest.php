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
 * This is a functional test that relies on a specific configuration of ActiveMQ, specifically, it relies on the usage
 * of the plugin <destinationPathSeparatorPlugin/> which should be enabled in the configuration file of activemq.
 *
 * e.g.: /opt/apache-activemq-5.12.0/conf/activemq.xml
 *
 * Class QueueConsumerTest
 */
class QueueConsumerTest extends BaseKernelTestCase
{
    const queue_prefix = '/test/command';
    const queue1 = '/test/command/1';
    const queue2 = '/test/command/2';
    const queue3 = '/test/command/3';

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

    public function handleSignal()
    {
        $consumerDriver = $this->getConsumer();
        $consumerDriver->stop();
        $this->fail('The queue consumer seems to be in an endless loop, please check if you enabled the destinationPathSeparatorPlugin in ActiveMQ');
    }

    public function testExecute()
    {
        $consumer = $this->getConsumer();
        $queueDriver = $this->getQueueDriver('main');
        $queueDriver->connect();

        $message1 = $this->createMessage(new EntityX(111));
        $msg = $queueDriver->createQueueMessage();
        $msg->setQueue(self::queue1);
        $msg->setHeader(Message::HEADER_FROM, self::queue1);
        $msg->setBody($message1);
        $msg->setDestinationURI('direct://test');
        $queueDriver->send($msg);

        $message2 = $this->createMessage(new EntityX(222));
        $msg = $queueDriver->createQueueMessage();
        $msg->setQueue(self::queue2);
        $msg->setHeader(Message::HEADER_FROM, self::queue2);
        $msg->setBody($message2);
        $msg->setDestinationURI('direct://test');
        $queueDriver->send($msg);

        $message3 = $this->createMessage(new EntityX(333));
        $msg = $queueDriver->createQueueMessage();
        $msg->setQueue(self::queue3);
        $msg->setHeader(Message::HEADER_FROM, self::queue3);
        $msg->setBody($message3);
        $msg->setDestinationURI('direct://test');
        $queueDriver->send($msg);

        $messages = [$message1, $message2, $message3];
        $queues = [self::queue1, self::queue2, self::queue3];

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
            QueueProtocol::OPTION_PREFIX => self::queue_prefix,
            QueueProtocol::OPTION_QUEUE_NAME => '/*',
        ]);

        $endpoint = new Endpoint('xxx', $opts, $queueProtocol, null, $consumer, $handlerMock);

        $consumer->setExpirationCount(3);   // This will mnake the consumer stop after reading 3 messages

        declare (ticks = 1);
        pcntl_signal(SIGALRM, [$this, 'handleSignal']);

        pcntl_alarm(5);
        $consumer->consume($endpoint);
        pcntl_alarm(0);
    }
}
