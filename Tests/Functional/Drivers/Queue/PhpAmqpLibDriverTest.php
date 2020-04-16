<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Drivers\Queue;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPProtocolException;
use PhpAmqpLib\Message\AMQPMessage;
use Smartbox\CoreBundle\Type\Entity;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\AsyncQueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

/**
 * Class PhpAmqpLibDriverTest.
 */
class PhpAmqpLibDriverTest extends AbstractQueueDriverTest
{
    /**
     * {@inheritdoc}
     */
    protected function createDriver(): AsyncQueueDriverInterface
    {
        return $this->getContainer()->get('smartesb.drivers.queue.amqp');
    }

    /**
     * Test the connection set in the driver initialization.
     */
    public function testConnection()
    {
        $this->assertTrue($this->driver->isConnected());
    }

    /**
     * Tests the normal process of publish messages.
     *
     * @dataProvider getMessages
     *
     * @throws \Exception
     */
    public function testSend(MessageInterface $msg)
    {
        $msgIn = $this->createQueueMessage($msg);
        $msgIn->addHeader('test_header', '12345');
        $this->assertTrue($this->driver->send($msgIn));
    }

    /**
     * Test the consume process without a callback in the return.
     */
    public function testConsumeWithoutCallback()
    {
        $this->assertNull($this->driver->consume('consumer-tag', $this->queueName));
    }

    /**
     * Tests the consume process with a callback in return that receives the messages and ack this after.
     *
     * @dataProvider getMessages
     */
    public function testConsumeWithCallbackAckingMessage(MessageInterface $msg)
    {
        $this->prepareToConsume($msg);

        $amqpMessage = null;
        $callback = function (AMQPMessage $message) use (&$amqpMessage) {
            $amqpMessage = $message;
            $queueMessage = new QueueMessage();
            $queueMessage->setMessageId($amqpMessage->getDeliveryTag());
            $this->driver->ack($queueMessage);
        };

        $this->driver->consume('consumer-tag', $this->queueName, $callback);
        $this->driver->wait();

        $this->assertInstanceOf(AMQPMessage::class, $amqpMessage);
        $this->assertEquals($amqpMessage->delivery_info['routing_key'], $this->queueName);
        $this->assertEquals($amqpMessage->delivery_info['consumer_tag'], 'consumer-tag');
        $this->assertNotNull($amqpMessage->getBody());
    }

    /**
     * Tests the consume process with a callback in return that receives the messages and nack this after.
     *
     * @dataProvider getMessages
     */
    public function testConsumeWithCallbackNackingMessage(MessageInterface $msg)
    {
        $this->prepareToConsume($msg);
        $amqpMessage = null;

        $callback = function ($message) use (&$amqpMessage) {
            $amqpMessage = $message;
            $queueMessage = new QueueMessage();
            $queueMessage->setMessageId($amqpMessage->getDeliveryTag());
            $this->driver->nack($queueMessage);
        };

        $this->driver->consume('consumer-tag', $this->queueName, $callback);
        $this->driver->wait();

        $this->assertInstanceOf(AMQPMessage::class, $amqpMessage);
        $this->assertEquals($amqpMessage->delivery_info['routing_key'], $this->queueName);
        $this->assertEquals($amqpMessage->delivery_info['consumer_tag'], 'consumer-tag');
        $this->assertNotNull($amqpMessage->getBody());
    }

    /**
     * Tests the function to destroy the connection and clean the variables related.
     */
    public function testDestroy()
    {
        $driver = $this->createDriver();
        $driver->connect();
        $this->assertTrue($driver->isConnected());

        $driver->consume('consumer-name', $this->queueName);
        $driver->destroy('consumer-tag');
        $this->assertFalse($driver->isConnected());
    }

    /**
     * Tests that tries to declare a channel without having a connection.
     */
    public function testDeclareChannelWithoutConnection()
    {
        $this->expectException(AMQPConnectionClosedException::class);
        $this->expectExceptionMessage('Broken pipe or closed connection');

        $driver = $this->createDriver();
        $driver->disconnect();
        $this->assertFalse($driver->isConnected());
        $driver->declareChannel();
    }

    /**
     * Test the format parameter set in the class.
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
        $driver = $this->createDriver();
        $driver->configure('', '', '', '');
        $driver->connect();
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

        $callback = function ($message) use (&$amqpMessage) {
            $amqpMessage = $message;
            $queueMessage = new QueueMessage();
            $queueMessage->setMessageId($message->getDeliveryTag());
            $this->driver->ack($queueMessage);
        };

        $this->driver->consume('consumer-tag', $this->queueName, $callback);
        $this->driver->waitNoBlock();

        $this->assertInstanceOf(AMQPMessage::class, $amqpMessage);
        $this->assertEquals($amqpMessage->delivery_info['routing_key'], $this->queueName);
        $this->assertEquals($amqpMessage->delivery_info['consumer_tag'], 'consumer-tag');
        $this->assertNotNull($amqpMessage->getBody());
    }

    /**
     * Prepare the data and class to consume a message.
     *
     * @return mixed
     */
    private function prepareToConsume(MessageInterface $msg)
    {
        $msgIn = $this->createQueueMessage($msg);
        $msgIn->addHeader('test_header', '12345');
        $this->driver->send($msgIn);
        $this->driver->disconnect();
        $this->driver->connect();
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

        $callback = function (AMQPMessage $amqpMessage) use ($queueMessage) {
            $this->assertEquals($amqpMessage->get('expiration'), $queueMessage->getHeader('expiration'), sprintf('Expiration header was missing or different. Expected %s, got %s', $queueMessage->getHeader('expiration'), $amqpMessage->get('expiration')));
            $this->assertEquals($amqpMessage->get('priority'), $queueMessage->getPriority(), sprintf('Priority header was missing or different. Expected %s, got %s', $queueMessage->getHeader('priority'), $amqpMessage->get('priority')));
            $this->assertEquals($amqpMessage->get('type'), $queueMessage->getMessageType(), sprintf('Type header was missing or different. Expected %s, got %s', $queueMessage->getHeader('type'), $amqpMessage->get('type')));
            $this->assertEquals($amqpMessage->get('delivery_mode'), QueueMessage::DELIVERY_MODE_PERSISTENT, sprintf('Delivery mode header was missing or different. Expected %s, got %s', QueueMessage::DELIVERY_MODE_PERSISTENT, $queueMessage->getHeader('delivery_mode')));
            $this->assertEquals($amqpMessage->get('application_headers')->getNativeData(), $queueMessage->getHeaders(), 'Application Headers (meaning, all headers of the message, compatible and incompatible with AMQP headers) were missing or different to what was expected.');
        };

        $this->driver->consume('consumer-tag', $this->queueName, $callback);
        $this->driver->wait();
    }
}
