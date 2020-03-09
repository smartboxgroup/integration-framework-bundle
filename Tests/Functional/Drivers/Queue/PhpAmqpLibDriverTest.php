<?php

namespace Smartbox\Integration\FrameworkBundle\Tests;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\AsyncQueueConsumer;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\PhpAmqpLibDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\Drivers\Queue\AbstractQueueDriverTest;

/**
 * Class PhpAmqpLibDriverTest
 * @group php-amqp-lib
 */
class PhpAmqpLibDriverTest extends AbstractQueueDriverTest
{

    /**
     * @var ConsumerInterface
     */
    protected $consumer;

    /**
     * @inheritDoc
     */
    protected function createDriver(): QueueDriverInterface
    {
        return $this->getContainer()->get('smartesb.drivers.queue.amqp');
    }

    /**
     * Returns an instance of the AsyncQueueConsumer class
     *
     * @return mixed
     */
    protected function createConsumer()
    {
        return $this->getContainer()->get('smartesb.async_consumers.queue');
    }

    /**
     * Test the connection set in the driver initialization
     *
     * @group amqp-connection
     */
    public function testConnection()
    {
        $this->assertInstanceOf(AMQPStreamConnection::class, $this->driver->declareChannel()->getConnection());
        $this->assertTrue($this->driver->isConnected());
    }

    /**
     * Tests the normal process of publish messages
     *
     * @dataProvider getMessages
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
     * Test the consume process without a callback in the return
     *
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
     * Tests the consume process with a callback in return that receives the messages and ack this after
     *
     * @dataProvider getMessages
     * @group amqp-consume-callback
     */
    public function testConsumeWithCallbackAckingMessage(MessageInterface $msg)
    {
        $this->prepareToConsume($msg);

        $amqpMessage = null;
        $callback = function(AMQPMessage $message) use (&$amqpMessage){
            $amqpMessage = $message;
            $queueMessage = new QueueMessage();
            $queueMessage->setMessageId($amqpMessage->getDeliveryTag());
            $this->driver->ack($queueMessage);
        };

        $this->driver->consume($this->consumer->getName(), $this->queueName, $callback);
        $this->driver->isConsuming();
        $this->driver->wait();

        $this->assertInstanceOf(AMQPMessage::class, $amqpMessage);
        $this->assertEquals($amqpMessage->delivery_info['routing_key'], $this->queueName);
        $this->assertInstanceOf(AsyncQueueConsumer::class, $this->consumer);
        $this->assertEquals($amqpMessage->delivery_info['consumer_tag'], $this->consumer->getName());
        $this->assertContains('QueueMessage', $amqpMessage->getBody());
    }

    /**
     * Tests the consume process with a callback in return that receives the messages and nack this after
     *
     * @dataProvider getMessages
     * @group amqp-consume-callback-nack
     */
    public function testConsumeNackingMessage(MessageInterface $msg)
    {
        $this->prepareToConsume($msg);
        $amqpMessage = null;

        $callback = function($message) use (&$amqpMessage) {
            $amqpMessage =  $message;
            $queueMessage = new QueueMessage();
            $queueMessage->setMessageId($amqpMessage->getDeliveryTag());
            $this->driver->nack($queueMessage);
        };

        $this->driver->consume($this->consumer->getName(), $this->queueName, $callback);
        $this->driver->isConsuming();
        $this->driver->wait();

        $this->assertInstanceOf(AMQPMessage::class, $amqpMessage);
        $this->assertEquals($amqpMessage->delivery_info['routing_key'], $this->queueName);
        $this->assertInstanceOf(AsyncQueueConsumer::class, $this->consumer);
        $this->assertEquals($amqpMessage->delivery_info['consumer_tag'], $this->consumer->getName());
        $this->assertContains('QueueMessage', $amqpMessage->getBody());
    }

    /**
     * Tests the function to destroy the connection and clean the variables related
     *
     * @group destroy
     */
    public function testDestroy()
    {
        $this->assertTrue($this->driver->isConnected());
        $this->driver->declareChannel();
        $this->driver->destroy($this->createConsumer());
        $this->assertFalse($this->driver->isConnected());
    }

    /**
     * Tests the function to destroy the connection and clean the variables related
     *
     * @group destroy-no-channel
     * @expectedException \AMQPChannelException
     */
    public function testDestroyWithoutChannel()
    {
        $this->assertTrue($this->driver->isConnected());
        $this->driver->destroy($this->createConsumer());
        $this->assertTrue($this->driver->isConnected());
    }

    /**
     * Tests that tries to declare a channel without having a connection
     *
     * @group channel-exception
     * @expectedException \Exception
     */
    public function testDeclareChannelWithoutConnnection()
    {
        $this->assertTrue($this->driver->isConnected());
        $this->driver->declareChannel();
        $this->driver->destroy($this->createConsumer());
        $this->assertFalse($this->driver->isConnected());
        $this->driver->declareChannel();
    }

    /**
     * @group format
     */
    public function testFormat()
    {
        $format = 'text/plain';
        $this->driver->setFormat($format);
        $this->assertEquals($format, $this->driver->getFormat());
    }

    /**
     * @group amqp-connection
     * @expectedException \AMQPConnectionException
     */
    public function testConnectWithoutData()
    {
        $this->driver->declareChannel();
        $this->driver->destroy($this->createConsumer());
        $this->driver->configure('', '', '', '');
        $this->driver->connect(true);
    }

    /**
     * @group amqp-connection2
     */
    public function testConnectMultipleHosts()
    {
        $this->driver->setPort(PhpAmqpLibDriver::DEFAULT_PORT);
        $this->driver->configure('rabbit', 'guest', 'guest', 'test');
        $this->driver->connect(true);
        $this->driver->declareChannel();
        $connections = $this->driver->getAvailableConnections();
        $this->assertInstanceOf(AMQPStreamConnection::class, $connections[0]);
        $this->assertInstanceOf(AMQPStreamConnection::class, $connections[1]);
        $this->assertCount(2, $connections);
    }

    /**
     * @dataProvider getMessages
     * @group amqp-consume-callback
     */
    public function testConsumeWaitNoBlock(MessageInterface $msg)
    {
        $this->prepareToConsume($msg);
        $amqpMessage = null;

        $callback = function($message) use (&$amqpMessage){
            $amqpMessage = $message;
            $queueMessage = new QueueMessage();
            $queueMessage->setMessageId($message->getDeliveryTag());
            $this->driver->ack($queueMessage);
        };

        $this->driver->consume($this->consumer->getName(), $this->queueName, $callback);
        $this->driver->isConsuming();
        $this->driver->waitNoBlock();

        $this->assertInstanceOf(AMQPMessage::class, $amqpMessage);
        $this->assertEquals($amqpMessage->delivery_info['routing_key'], $this->queueName);
        $this->assertInstanceOf(AsyncQueueConsumer::class, $this->consumer);
        $this->assertEquals($amqpMessage->delivery_info['consumer_tag'], $this->consumer->getName());
        $this->assertContains('QueueMessage', $amqpMessage->getBody());
    }

    /**
     * Prepare the data and class to consume a message
     *
     * @param QueueMessage $msg
     * @return mixed
     */
    public function prepareToConsume(MessageInterface $msg)
    {
        $this->endpoint = $this->createMock(EndpointInterface::class);
        $msgIn = $this->createQueueMessage($msg);
        $msgIn->addHeader('test_header', '12345');
        $this->driver->send($msgIn);
        $this->consumer = $this->createConsumer();
        $this->driver->declareChannel();
    }

}