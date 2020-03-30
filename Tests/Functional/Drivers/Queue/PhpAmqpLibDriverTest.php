<?php

namespace Smartbox\Integration\FrameworkBundle\Tests;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPProtocolException;
use PhpAmqpLib\Message\AMQPMessage;
use Smartbox\CoreBundle\Type\Entity;
use Smartbox\Integration\FrameworkBundle\Components\Queues\AsyncQueueConsumer;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
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
        return $this->setMockConsumer();
    }

    /**
     * Test the connection set in the driver initialization
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
     */
    public function testSend(MessageInterface $msg)
    {
        $msgIn = $this->createQueueMessage($msg);
        $msgIn->addHeader('test_header', '12345');
        $this->assertTrue($this->driver->send($msgIn));
    }

    /**
     * Test the consume process without a callback in the return
     */
    public function testConsumeWithoutCallback()
    {
        $this->consumer = $this->createConsumer();
        $this->driver->declareChannel();
        $this->driver->declareQueue($this->queueName, QueueMessage::DELIVERY_MODE_PERSISTENT, []);
        $return = $this->driver->consume($this->consumer->getName(), $this->queueName);
        $this->assertNull($return);
    }

    /**
     * Tests the consume process with a callback in return that receives the messages and ack this after
     *
     * @dataProvider getMessages
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
        $this->driver->declareChannel()->is_consuming();
        $this->driver->wait();

        $this->assertInstanceOf(AMQPMessage::class, $amqpMessage);
        $this->assertEquals($amqpMessage->delivery_info['routing_key'], $this->queueName);
        $this->assertEquals($amqpMessage->delivery_info['consumer_tag'], $this->consumer->getName());
        $this->assertNotNull($amqpMessage->getBody());
    }

    /**
     * Tests the consume process with a callback in return that receives the messages and nack this after
     *
     * @dataProvider getMessages
     */
    public function testConsumeWithCallbackNackingMessage(MessageInterface $msg)
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
        $this->driver->declareChannel()->is_consuming();
        $this->driver->wait();

        $this->assertInstanceOf(AMQPMessage::class, $amqpMessage);
        $this->assertEquals($amqpMessage->delivery_info['routing_key'], $this->queueName);
        $this->assertEquals($amqpMessage->delivery_info['consumer_tag'], $this->consumer->getName());
        $this->assertNotNull($amqpMessage->getBody());
    }

    /**
     * Tests the function to destroy the connection and clean the variables related
     *
     * @dataProvider getMessages
     * @group destroy
     */
    public function testDestroy(MessageInterface $msg)
    {
        $this->prepareToConsume($msg);
        $this->driver->declareChannel();
        $this->assertTrue($this->driver->isConnected());
        $this->driver->consume($this->consumer->getName(), $this->queueName);
        $this->driver->destroy($this->consumer->getName());
        $this->assertFalse($this->driver->isConnected());
    }

     /**
     * Tests that tries to declare a channel without having a connection
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
     * Test the format parameter set in the class
     */
    public function testFormat()
    {
        $format = 'text/plain';
        $this->driver->setFormat($format);
        $this->assertEquals($format, $this->driver->getFormat());
    }

    /**
     * @throws AMQPProtocolException
     */
    public function testConnectWithoutData()
    {
        $this->driver->declareChannel();
        $consumer = $this->createConsumer();
        $this->driver->destroy($consumer->getName());
        $this->driver->configure('', '', '', '');
        $this->driver->connect();
        $this->assertTrue($this->driver->isConnected());
        $this->assertInstanceOf(AMQPStreamConnection::class, $this->driver->declareChannel()->getConnection());
    }

    /**
     * @dataProvider getMessages
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
        $this->driver->declareChannel()->is_consuming();
        $this->driver->waitNoBlock();

        $this->assertInstanceOf(AMQPMessage::class, $amqpMessage);
        $this->assertEquals($amqpMessage->delivery_info['routing_key'], $this->queueName);
        $this->assertEquals($amqpMessage->delivery_info['consumer_tag'], $this->consumer->getName());
        $this->assertNotNull($amqpMessage->getBody());
    }

    /**
     * Prepare the data and class to consume a message
     *
     * @param MessageInterface $msg
     * @return mixed
     */
    public function prepareToConsume(MessageInterface $msg)
    {
        $msgIn = $this->createQueueMessage($msg);
        $msgIn->addHeader('test_header', '12345');
        $this->driver->send($msgIn);
        $this->driver->disconnect();
        $this->driver->connect();
        $this->consumer = $this->createConsumer();
    }

    /**
     * Test that the QueueMessageInterface header compatible with AMQP Headers are sent to the queue and back to
     * the consumer without changes.
     */
    public function testAMQPMessageHeaders()
    {
        $queueMessage = $this->createQueueMessage(new Entity());
        $queueMessage->setTTL(rand(60, 600));
        $queueMessage->setPriority(rand(0, 255));
        $queueMessage->setMessageType(md5(rand(0, 255)));
        $queueMessage->setPersistent(true);

        $this->driver->send($queueMessage);
        $this->consumer = $this->createConsumer();

        $callback = function (AMQPMessage $amqpMessage) use ($queueMessage) {
            $this->assertEquals($amqpMessage->get('expiration'), $queueMessage->getHeader('expiration'), sprintf('Expiration header was missing or different. Expected %s, got %s', $queueMessage->getHeader('expiration'), $amqpMessage->get('expiration')));
            $this->assertEquals($amqpMessage->get('priority'), $queueMessage->getPriority(), sprintf('Priority header was missing or different. Expected %s, got %s', $queueMessage->getHeader('priority'), $amqpMessage->get('priority')));
            $this->assertEquals($amqpMessage->get('type'), $queueMessage->getMessageType(), sprintf('Type header was missing or different. Expected %s, got %s', $queueMessage->getHeader('type'), $amqpMessage->get('type')));
            $this->assertEquals($amqpMessage->get('delivery_mode'), QueueMessage::DELIVERY_MODE_PERSISTENT, sprintf('Delivery mode header was missing or different. Expected %s, got %s', QueueMessage::DELIVERY_MODE_PERSISTENT, $queueMessage->getHeader('delivery_mode')));
            $this->assertEquals($amqpMessage->get('application_headers')->getNativeData(), $queueMessage->getHeaders(), 'Application Headers (meaning, all headers of the message, compatible and incompatible with AMQP headers) were missing or different to what was expected.');
        };

        $this->driver->consume($this->consumer->getName(), $this->queueName, $callback);
        $this->driver->wait();
    }
}
