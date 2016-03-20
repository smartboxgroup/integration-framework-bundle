<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Consumers;

use Smartbox\Integration\FrameworkBundle\Core\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Tests\BaseKernelTestCase;
use Smartbox\Integration\FrameworkBundle\Tests\EntityX;

/**
 * This is a functional test that relies on a specific configuration of ActiveMQ, specifically, it relies on the usage
 * of the plugin <destinationPathSeparatorPlugin/> which should be enabled in the configuration file of activemq
 *
 * e.g.: /opt/apache-activemq-5.12.0/conf/activemq.xml
 *
 * Class QueueConsumerTest
 * @package Smartbox\Integration\PlatformBundle\Tests\Command
 */
class QueueConsumerTest extends BaseKernelTestCase
{
    const queue_prefix = '/test/command';
    const queue1 = '/test/command/1';
    const queue2 = '/test/command/2';
    const queue3 = '/test/command/3';

    /**
     * @return object
     */
    private function getConsumerDriver()
    {
        return $this->helper->getConsumer('queue.main');
    }

    /**
     * @param string $queueDriverName
     * @return object
     */
    private function getQueueDriver($queueDriverName)
    {
        return $this->helper->getQueueDriver($queueDriverName);
    }

    public function handleSignal(){
        $consumerDriver = $this->getConsumerDriver();
        $consumerDriver->stop();
        $this->fail("The queue consumer seems to be in an endless loop, please check if you enabled the destinationPathSeparatorPlugin in ActiveMQ");
    }

    public function testExecute()
    {
        $consumer = $this->getConsumerDriver();
        $queueDriver = $this->getQueueDriver('default');
        $queueDriver->connect();

        $message1 = $this->createMessage(new EntityX(111));
        $msg = $queueDriver->createQueueMessage();
        $msg->setQueue(self::queue1);
        $msg->setHeader(Message::HEADER_FROM,self::queue1);
        $msg->setBody($message1);
        $queueDriver->send($msg);

        $message2 = $this->createMessage(new EntityX(222));
        $msg = $queueDriver->createQueueMessage();
        $msg->setQueue(self::queue2);
        $msg->setHeader(Message::HEADER_FROM,self::queue2);
        $msg->setBody($message2);
        $queueDriver->send($msg);

        $message3 = $this->createMessage(new EntityX(333));
        $msg = $queueDriver->createQueueMessage();
        $msg->setQueue(self::queue3);
        $msg->setHeader(Message::HEADER_FROM,self::queue3);
        $msg->setBody($message3);
        $queueDriver->send($msg);

        $messages = array($message1,$message2, $message3);
        $queues = array(self::queue1,self::queue2,self::queue3);

        $handlerMock = $this->getMock(MessageHandler::class);
        $handlerMock->expects($this->exactly(3))->method('handle')
            ->with($this->callback(
                function($message) use ($messages){
                    $res = array_search($message,$messages);
                    if($res !== false){
                        unset($messages[$res]);
                        return true;
                    }
                    return false;
                }
            ),$this->callback(
                function($queue) use ($queues){
                    $res = array_search($queue,$queues);
                    if($res !== false){
                        unset($queues[$res]);
                        return true;
                    }
                    return false;
                }
            ))
            ->willReturn(true);

        $consumer->setHandler($handlerMock);
        $consumer->setQueueDriver($queueDriver);
        $consumer->setExpirationCount(3);   // This will mnake the consumer stop after reading 3 messages

        declare(ticks = 1);
        pcntl_signal(SIGALRM, [$this, 'handleSignal']);

        pcntl_alarm(5);
        $consumer->consume(self::queue_prefix.'/*');
        pcntl_alarm(0);
    }
}