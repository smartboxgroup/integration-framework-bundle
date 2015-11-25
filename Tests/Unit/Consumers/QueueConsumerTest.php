<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Consumers;

use Smartbox\Integration\FrameworkBundle\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
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

    public function setUp(){
        $this->bootKernel();
    }

    public function handleSignal(){
        $container = self::$kernel->getContainer();
        $container->get('smartbox.consumers.queue.main')->stop();
        $this->fail("The queue consumer seems to be in an endless loop, please check if you enabled the destinationPathSeparatorPlugin in ActiveMQ");
    }

    public function testExecute()
    {
        $container = self::$kernel->getContainer();
        $consumer = $container->get('smartbox.consumers.queue.main');

        $driver = $container->get('smartbox.queue.driver.main');
        $driver->connect();

        $message1 = new Message(new EntityX(111));
        $msg = $driver->createQueueMessage();
        $msg->setQueue(self::queue1);
        $msg->setHeader(Message::HEADER_FROM,self::queue1);
        $msg->setBody($message1);
        $driver->send($msg);

        $message2 = new Message(new EntityX(222));
        $msg = $driver->createQueueMessage();
        $msg->setQueue(self::queue2);
        $msg->setHeader(Message::HEADER_FROM,self::queue2);
        $msg->setBody($message2);
        $driver->send($msg);

        $message3 = new Message(new EntityX(333));
        $msg = $driver->createQueueMessage();
        $msg->setQueue(self::queue3);
        $msg->setHeader(Message::HEADER_FROM,self::queue3);
        $msg->setBody($message3);
        $driver->send($msg);

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
        $consumer->setQueueDriver($driver);
        $consumer->setExpirationCount(3);   // This will mnake the consumer stop after reading 3 messages

        declare(ticks = 1);
        pcntl_signal(SIGALRM, [$this, 'handleSignal']);

        pcntl_alarm(5);
        $consumer->consume(self::queue_prefix.'/*');
        pcntl_alarm(0);
    }
}