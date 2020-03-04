<?php

namespace Smartbox\Integration\FrameworkBundle\Tests;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\AsyncQueueConsumer;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\PhpAmqpLibDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\Drivers\Queue\AbstractQueueDriverTest;

/**
 * Class PhpAmqpLibDriverTest
 * @package Smartbox\Integration\FrameworkBundle\Tests
 * @group php-amqp-lib
 */
class PhpAmqpLibDriverTest extends AbstractQueueDriverTest
{

    /**
     * @var ConsumerInterface
     */
    protected $consumer;

    /** @var AMQPMessage */
    protected $message;

    /**
     * @inheritDoc
     */
    protected function createDriver(): QueueDriverInterface
    {
        return $this->getContainer()->get('smartesb.drivers.queue.amqp');
    }

    protected function createConsumer()
    {
        return $this->getContainer()->get('smartesb.async_consumers.queue');
    }

    /**
     * @group amqp-connection
     */
    public function testConnection()
    {
        $this->assertInstanceOf(AMQPStreamConnection::class, $this->driver->declareChannel()->getConnection());
        $this->assertTrue($this->driver->isConnected());
    }

    /**
     * @dataProvider getMessages
     *
     * @param MessageInterface $msg
     * @throws \Exception
     * @group amqp-send
     */
    public function testSend(MessageInterface $msg)
    {
        $msgIn = $this->createQueueMessage($msg);
        $msgIn->addHeader('test_header', '12345');
        $this->assertTrue($this->driver->send($msgIn));
    }

    /**
     * @group amqp-consume-no-channel
     * @expectedException \AMQPChannelException
     */
    public function testConsumeWithoutChannel()
    {
        $this->consumer = $this->createConsumer();
        $consumerTag = $this->consumer->getName();
        $this->driver->consume($consumerTag, $this->queueName);
    }

    /**
     * @group amqp-consume-no-queue
     * @expectedException \PhpAmqpLib\Exception\AMQPProtocolChannelException
     */
    public function testConsumeWithoutQueue()
    {
        $this->consumer = $this->createConsumer();
        $consumerTag = $this->consumer->getName();
        $this->driver->declareChannel();
        $this->driver->consume($consumerTag, $this->queueName);
    }

    /**
     * @group amqp-consume-no-callback
     */
    public function testConsumeWithoutCallback()
    {
        $this->consumer = $this->createConsumer();
        $consumerTag = $this->consumer->getName();
        $this->driver->declareChannel();
        $this->driver->declareQueue($this->queueName, AMQP_DURABLE, []);
        $return = $this->driver->consume($consumerTag, $this->queueName);
        $this->assertNull($return);
    }

    /**
     * @dataProvider getMessages
     * @group amqp-consume-callback
     */
    public function testConsumeWithCallbackAckingMessage(MessageInterface $msg)
    {
        $msgIn = $this->createQueueMessage($msg);
        $msgIn->addHeader('test_header', '12345');
        $this->driver->send($msgIn);
        $this->consumer = $this->createConsumer();
        $consumerTag = $this->consumer->getName();
        $channel = $this->driver->declareChannel();

        $callback = function($message) use ($channel) {
            $this->message = $message;
            $this->driver->ack($this->message->delivery_info['delivery_tag']);
        };

        $message = $this->driver->consume($consumerTag, $this->queueName, $callback);
        $this->driver->isConsuming();
        $this->driver->wait();

        $this->assertInstanceOf(AMQPMessage::class, $this->message);
        $this->assertEquals($this->message->delivery_info['routing_key'], $this->queueName);
        $this->assertInstanceOf(AsyncQueueConsumer::class, $this->consumer);
        $this->assertEquals($this->message->delivery_info['consumer_tag'], $this->consumer->getName());
        $this->assertContains('QueueMessage', $this->message->getBody());
    }

    /**
     * @dataProvider getMessages
     * @group amqp-consume-callback-ack2
     */
    public function testConsumeWithCallbackAckingTwice(MessageInterface $msg)
    {
        $msgIn = $this->createQueueMessage($msg);
        $msgIn->addHeader('test_header', '12345');
        $this->driver->send($msgIn);
        $this->consumer = $this->createConsumer();
        $consumerTag = $this->consumer->getName();
        $channel = $this->driver->declareChannel();

        $callback = function($message) use ($channel) {
            $this->message = $message;
            $this->driver->ack($this->message->delivery_info['delivery_tag']);
        };

        $message = $this->driver->consume($consumerTag, $this->queueName, $callback);
        $this->driver->isConsuming();
        $this->driver->wait();
        $this->driver->ack($this->message->delivery_info['delivery_tag']);

        $this->assertInstanceOf(AMQPMessage::class, $this->message);
        $this->assertEquals($this->message->delivery_info['routing_key'], $this->queueName);
        $this->assertInstanceOf(AsyncQueueConsumer::class, $this->consumer);
        $this->assertEquals($this->message->delivery_info['consumer_tag'], $this->consumer->getName());
        $this->assertContains('QueueMessage', $this->message->getBody());
    }

    /**
     * @dataProvider getMessages
     * @group amqp-consume-callback-nack
     */
    public function testConsumeNackingMessage(MessageInterface $msg)
    {
        $msgIn = $this->createQueueMessage($msg);
        $msgIn->addHeader('test_header', '12345');
        $this->driver->send($msgIn);
        $this->consumer = $this->createConsumer();
        $consumerTag = $this->consumer->getName();
        $channel = $this->driver->declareChannel();

        $callback = function($message) use ($channel) {
            $this->message = $message;
            $this->driver->nack($this->message->delivery_info['delivery_tag']);
        };

        $message = $this->driver->consume($consumerTag, $this->queueName, $callback);
        $this->driver->isConsuming();
        $this->driver->wait();

        $this->assertInstanceOf(AMQPMessage::class, $this->message);
        $this->assertEquals($this->message->delivery_info['routing_key'], $this->queueName);
        $this->assertInstanceOf(AsyncQueueConsumer::class, $this->consumer);
        $this->assertEquals($this->message->delivery_info['consumer_tag'], $this->consumer->getName());
        $this->assertContains('QueueMessage', $this->message->getBody());
    }

/*    public function testNack()
    {

    }

    public function testWaitNoBlock()
    {

    }

    public function testCreateQueueMessage()
    {

    }

    public function testWait()
    {

    }

    public function testDeclareExchange()
    {

    }

    public function testDestroy()
    {

    }

    public function testDeclareChannel()
    {

    }

    public function testSetFormat()
    {

    }

    public function testAddConnection()
    {

    }

    public function testIsConnected()
    {

    }

    public function testDisconnect()
    {

    }

    public function testSetPort()
    {

    }

    public function testDeclareQueue()
    {

    }


    public function testGetFormat()
    {

    }*/

}
