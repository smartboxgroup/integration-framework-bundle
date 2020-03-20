<?php

namespace Smartbox\Integration\FrameworkBundle\Tests;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPProtocolException;
use PhpAmqpLib\Message\AMQPMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\AsyncQueueConsumer;
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
     * @return AsyncQueueConsumer
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
        $this->driver->declareQueue($this->queueName, QueueMessage::DELIVERY_MODE_PERSISTENT, []);
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
        $this->driver->connect();
        $this->driver->declareChannel();
        $this->assertTrue($this->driver->isConnected());
        $this->driver->destroy($this->createConsumer()->getName());
        $this->assertFalse($this->driver->isConnected());
    }

     /**
     * Tests that tries to declare a channel without having a connection
     *
     * @group channel-exception
     */
    public function testDeclareChannelWithoutConnnection()
    {
        $this->expectException(AMQPConnectionClosedException::class);
        $this->expectExceptionMessage('Broken pipe or closed connection');
        $this->assertTrue($this->driver->isConnected());
        $this->driver->declareChannel();
        $this->driver->destroy($this->createConsumer()->getName());
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
     * @throws AMQPProtocolException
     */
    public function testConnectWithoutData()
    {
        $this->driver->declareChannel();
        $consumer = $this->createConsumer();
        $this->driver->destroy($consumer->getName());
        $this->driver->configure('', '', '', '');
        $this->driver->connect(true);
        $this->assertTrue($this->driver->isConnected());
        $this->assertInstanceOf(AMQPStreamConnection::class, $this->driver->declareChannel()->getConnection());
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

    /**
     * Test that the QueueMessageInterface header compatible with AMQP Headers are sent to the queue and back to
     * the consumer without changes.
     */
    public function testAMQPMessageHeaders()
    {
        $queueMessage = $this->createQueueMessage(new EntityX());
        $queueMessage->setTTL(rand(60, 600));
        $queueMessage->setPriority(rand(0, 255));
        $queueMessage->setMessageType(md5(rand(0, 255)));

        $this->driver->send($queueMessage);
        $this->driver->declareChannel();
        $this->consumer = $this->createConsumer();

        $callback = function(AMQPMessage $amqpMessage) use ($queueMessage){
            $this->assertEquals($amqpMessage->get('expiration'), $queueMessage->getHeader('expiration'));
            $this->assertEquals($amqpMessage->get('priority'), $queueMessage->getPriority());
            $this->assertEquals($amqpMessage->get('type'), $queueMessage->getMessageType());
        };

        $this->driver->consume($this->consumer->getName(), $this->queueName, $callback);
        $this->driver->wait();
    }
}
