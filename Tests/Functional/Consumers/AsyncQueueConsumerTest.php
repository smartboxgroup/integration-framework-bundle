<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Consumers;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Components\Queues\AsyncQueueConsumer;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\AsyncQueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueProtocol;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Serializers\QueueSerializerInterface;
use Smartbox\Integration\FrameworkBundle\Tests\Unit\Traits\ConsumerMockFactory;
use Smartbox\Integration\FrameworkBundle\Tools\Helper\SmartesbHelper;

/**
 * Class AsyncQueueConsumerTest.
 *
 * @group async-queue-consumer
 */
class AsyncQueueConsumerTest extends TestCase
{
    use ConsumerMockFactory;

    /**
     * Assert that the consume function passes the correct objects to the driver.
     */
    public function testConsume()
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint->expects($this->any())
            ->method('getOptions')
            ->willReturn(
                [
                    QueueProtocol::OPTION_QUEUE_DRIVER => 'ya-know-what?',
                    'prefix' => 'doughnuts-',
                    'queue' => 'should-be-plain',
                ]
            );

        $driver = $this->createMock(AsyncQueueDriverInterface::class);
        $driver->expects($this->once())
            ->method('consume')
            ->with(
                $this->isType('string'),
                $this->equalTo('doughnuts-should-be-plain'),
                $this->isInstanceOf(\Closure::class)
            );

        // Consumer is extended with an anon class to fake the consumption of a message
        $consumer = new class() extends AsyncQueueConsumer {
            public function waitNoBlock(EndpointInterface $endpoint)
            {
                --$this->expirationCount;
            }
        };
        $consumer->setSmartesbHelper($this->getHelper($driver));
        $consumer->setExpirationCount(1);

        $consumer->consume($endpoint, function () {});
    }

    /**
     * Callback should call the exception handler when it fails to deserialize a message. By default, the
     * ReThrowExceptionHandler is used which simply throws the exception again.
     */
    public function testUsesExceptionHandlerOnSerializationErrors()
    {
        $serializer = $this->createMock(QueueSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('decode')
            ->willThrowException(new \RuntimeException('I cöuld nót dese�rialize that JSON strin��������'));

        $driver = $this->createMock(AsyncQueueDriverInterface::class);

        $messageHeaders = new AMQPTable([
            'ttl' => 86400,
            'expiration' => 86400000,
            'expires' => 1584634937000,
            'destination' => 'api-test',
            'priority' => 4,
        ]);

        $message = $this->getMockBuilder(AMQPMessage::class)
            ->setMethods(['setBody'])
            ->getMock();
        $message->method('setBody')->with('an amqp message');
        $message->set('application_headers', $messageHeaders);
        $message->delivery_info = ['delivery_tag' => 1];

        $consumer = $this->getConsumer($this, AsyncQueueConsumer::class, $message, 1);
        $consumer->setSmartesbHelper($this->getHelper($driver));
        $consumer->setSerializer($serializer);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('I cöuld nót dese�rialize that JSON strin��������');

        $consumer->consume($this->createMock(EndpointInterface::class));
    }

    /**
     * Test that the message id is set when deserializing it, so it can be correctly acked later.
     */
    public function testConsumerSetsMessageID()
    {
        $messageID = 42;
        $queueMessage = $this->createMock(QueueMessage::class);
        $queueMessage->expects($this->once())
            ->method('setMessageId')
            ->with($messageID);

        $serializer = $this->createMock(QueueSerializerInterface::class);
        $serializer->expects($this->once())
            ->method('decode')
            ->willReturn($queueMessage);

        $properties['application_headers'] = new AMQPTable(['header' => 'I am AMKP']);

        $message = new AMQPMessage('an amqp message', $properties);
        $message->delivery_info['delivery_tag'] = $messageID;

        $consumer = $this->getConsumer($this, AsyncQueueConsumer::class, $message, 1, ['process']);

        $consumer->setSerializer($serializer);
        $consumer->setSmartesbHelper($this->getHelper($this->createMock(AsyncQueueDriverInterface::class)));

        $consumer->consume($this->createMock(EndpointInterface::class));
    }

    /**
     * Returns a SmartESBHelper mock that returns the passed driver on getQueueDriver().
     *
     * @return MockObject
     */
    protected function getHelper(AsyncQueueDriverInterface $driver)
    {
        $helper = $this->createMock(SmartesbHelper::class);
        $helper->method('getQueueDriver')
            ->willReturn($driver);

        return $helper;
    }
}
