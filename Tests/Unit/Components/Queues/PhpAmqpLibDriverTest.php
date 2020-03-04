<?php

namespace Smartbox\Integration\FrameworkBundle\Tests;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
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
        $this->driver->declareChannel();

        $callback = function($message) {
            $this->message = $message;
            $this->driver->ack($this->message->delivery_info['delivery_tag']);
        };

        $this->driver->consume($consumerTag, $this->queueName, $callback);
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

        $this->driver->consume($consumerTag, $this->queueName, $callback);
        $this->driver->isConsuming();
        $this->driver->wait();

        $this->assertInstanceOf(AMQPMessage::class, $this->message);
        $this->assertEquals($this->message->delivery_info['routing_key'], $this->queueName);
        $this->assertInstanceOf(AsyncQueueConsumer::class, $this->consumer);
        $this->assertEquals($this->message->delivery_info['consumer_tag'], $this->consumer->getName());
        $this->assertContains('QueueMessage', $this->message->getBody());
    }

    /**
     * @group destroy
     */
    public function testDestroy()
    {
        $this->assertTrue($this->driver->isConnected());
        $this->driver->destroy();
        $this->assertFalse($this->driver->isConnected());
    }

    /**
     * @group channel-exception
     * @expectedException \PhpAmqpLib\Exception\AMQPConnectionClosedException
     */
    public function testDeclareChannelWithoutConnnection()
    {
        $this->assertTrue($this->driver->isConnected());
        $this->driver->destroy();
        $this->assertFalse($this->driver->isConnected());
        $this->driver->declareChannel();
    }

    /**
     * @group amqp-exchange
     */
    public function testDeclareExchange()
    {
        $exchangeName = 'EAI';
        $channel = $this->driver->declareChannel();
        $this->assertInstanceOf(AMQPChannel::class, $channel);
        $this->assertNull($this->driver->declareExchange('EAI', AMQPExchangeType::DIRECT, false, true, false, false, false));
        $channnel->exchange_delete($exchangeName);
    }

    /**
     * @group amqp-queue-bind
     */
    public function testQueueBind()
    {
        $exchangeName = 'EAI';
        $channel = $this->driver->declareChannel();
        $this->assertInstanceOf(AMQPChannel::class, $channel);
        $this->driver->declareExchange($exchangeName, AMQPExchangeType::DIRECT, false, true, false, false, false);
        $this->driver->queueBind($this->queueName, $exchangeName);

    }

/*    public function testWaitNoBlock()
    {

    }

    public function testCreateQueueMessage()
    {

    }

    public function testWait()
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
