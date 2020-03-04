<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Consumers;

use JMS\Serializer\SerializerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Components\Queues\AsyncQueueConsumer;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\AsyncQueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;

/**
 * Class AsyncQueueConsumerTest
 * @group async-queue-consumer
 */
class AsyncQueueConsumerTest extends TestCase
{
    /**
     * Assert that the consume function passes the correct objects to the driver
     */
    public function testConsume()
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint->expects($this->once())
            ->method('getOptions')
            ->willReturn(
                ['prefix' => 'doughnuts-',
                    'queue' => 'should-be-plain']
            );

        $driver = $this->createMock(AsyncQueueDriverInterface::class);
        $driver->expects($this->once())
            ->method('consume')
            ->with(
                $this->isType('string'),
                $this->equalTo('doughnuts-should-be-plain'),
                $this->isInstanceOf(\Closure::class)
            );

        $consumer = new AsyncQueueConsumer();
        $consumer->setDriver($driver);

        $consumer->asyncConsume($endpoint, function () {
        });
    }

    /**
     * Assert that the callback function returns a callable object.
     */
    public function testCallbackReturnsCallable()
    {
        $consumer = new AsyncQueueConsumer();
        $callback = $consumer->callback($this->createMock(EndpointInterface::class));
        $this->assertInstanceOf(\Closure::class, $callback);
    }

    /**
     * Callback should call the exception handler when it fails to deserialize a message. By default, the
     * ReThrowExceptionHandler is used which simply throws the exception again.
     */
    public function testUsesExceptionHandlerOnSerializationErrors()
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->willThrowException(new \RuntimeException('I cöuld nót dese�rialize that JSON strin��������'));

        $driver = $this->createMock(AsyncQueueDriverInterface::class);
        $driver->expects($this->once())
            ->method('getFormat')
            ->willReturn('json');

        $consumer = new AsyncQueueConsumer();
        $consumer->setSerializer($serializer);
        $consumer->setDriver($driver);
        $callback = $consumer->callback($this->createMock(EndpointInterface::class));

        $message = new AMQPMessage('an amqp message', []);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('I cöuld nót dese�rialize that JSON strin��������');

        $callback($message);
    }

    public function testConsumerSetsMessageID()
    {
        $message = $this->createMock(QueueMessageInterface::class);
        $message->expects($this->once())
            ->method('setMessageId')
            ->with($this->equalTo(1));

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($message);

        $driver = $this->createMock(AsyncQueueDriverInterface::class);

        $consumer = $this->getMockBuilder(AsyncQueueConsumer::class)
            // Prevent the parent class from processing the message, otherwise it would require mocking buncha stuff
            ->setMethods(['process'])
            ->getMock();
        $consumer->setSerializer($serializer);
        $consumer->setDriver($driver);
        $callback = $consumer->callback($this->createMock(EndpointInterface::class));

        $message = new AMQPMessage('an amqp message');
        $message->delivery_info['delivery_tag'] = 1;

        $callback($message);
    }
}
