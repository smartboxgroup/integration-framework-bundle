<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Consumer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractAsyncConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Events\TimingEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class AbstractAsyncConsumerTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Consumer
 *
 * @group abstract-async-consumer
 * @group time-sensitive
 */
class AbstractAsyncConsumerTest extends TestCase
{
    /**
     * Test that the consumer installs a callback by calling asyncConsume with a callable.
     */
    public function testConsumeInstallsCallback()
    {
        $endpoint = $this->createMock(EndpointInterface::class);

        /** @var AbstractAsyncConsumer|MockObject $consumer */
        $consumer = $this->getMockForAbstractClass(AbstractAsyncConsumer::class);
        $consumer->expects($this->once())
            ->method('asyncConsume')
            ->with(
                $this->equalTo($endpoint),
                $this->isInstanceOf(\Closure::class)
            );

        // Prevent consumer from entering an infinite loop
        $consumer->stop();

        $consumer->consume($endpoint);
    }

    /**
     * Test that the consumer can be forced to leave the wait loop when a stop signal is issued
     */
    public function testConsumerIsStoppable()
    {
        /** @var AbstractAsyncConsumer|MockObject $consumer */
        $consumer = $this->getMockForAbstractClass(AbstractAsyncConsumer::class);
        $consumer->expects($this->once())
            ->method('waitNoBlock')
            ->willReturnCallback(function () use ($consumer) {
                //Stop consumer after "waiting" for one message.
                $consumer->stop();
            });

        $consumer->consume($this->createMock(EndpointInterface::class));
    }

    /**
     * Test that the consumer won't cause a CPU spike by usleeping x amount of microseconds if there's nothing to process
     */
    public function testConsumerSleepsWhenFlagIsSet()
    {
        /** @var AbstractAsyncConsumer|MockObject $consumer */
        $consumer = $this->getMockForAbstractClass(AbstractAsyncConsumer::class);
        $consumer->expects($this->once())
            ->method('waitNoBlock')
            ->willReturnCallback(
                function () use ($consumer) {
                    // Stop consumer after "waiting" for the one message.
                    $consumer->stop();
                });

        $stopwatch = new Stopwatch();
        $stopwatch->start('consume');
        $consumer->consume($this->createMock(EndpointInterface::class));
        $stopwatch->stop('consume');

        $this->assertGreaterThan(AbstractAsyncConsumer::SLEEP_DURATION / 1000, $stopwatch->getEvent('consume')->getDuration());
    }

    /**
     * Test that the consumer will run as fast as possible if it's told not to sleep.
     */
    public function testConsumerDoesNotSleepWhenFlagIsSet()
    {
        // Extend the class and on the WaitNoBlock function, let it run twice while setting the sleep flag to false
        $consumer = new class extends AbstractAsyncConsumer {
            protected $rounds = 0;
            protected function initialize(EndpointInterface $endpoint) {}

            protected function cleanUp(EndpointInterface $endpoint) {}

            protected function confirmMessage(EndpointInterface $endpoint, QueueMessageInterface $message) {}

            public function asyncConsume(EndpointInterface $endpoint, callable $callback) {}

            public function wait(EndpointInterface $endpoint) {}

            public function waitNoBlock(EndpointInterface $endpoint)
            {
                $this->sleep = false;
                $this->rounds++;

                if ($this->rounds > 2) {
                    $this->stop();
                }
            }
        };

        $stopwatch = new Stopwatch();
        $stopwatch->start('consume');
        $consumer->consume($this->createMock(EndpointInterface::class));
        $stopwatch->stop('consume');

        $this->assertLessThan(AbstractAsyncConsumer::SLEEP_DURATION / 1000, $stopwatch->getEvent('consume')->getDuration());
    }

    /**
     * Test that consumer dispatches an event with timing information
     */
    public function testCallbackMeasuresProcessingDuration()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isType('string'),
                $this->callback(function(TimingEvent $event){
                    return $event->getIntervalMs() > 0;
                }));

        /** @var AbstractAsyncConsumer|MockObject $consumer */
        $consumer = $this->getMockForAbstractClass(AbstractAsyncConsumer::class);
        $consumer->setEventDispatcher($dispatcher);
        $callback = $consumer->callback($this->createMock(EndpointInterface::class));

        $callback(new QueueMessage());
    }

    /**
     * Test that messages are confirmed once they were successfully processed
     */
    public function testMessageIsConfirmedAfterProcessing()
    {
        $message = new QueueMessage();

        /** @var AbstractAsyncConsumer|MockObject $consumer */
        $consumer = $this->getMockForAbstractClass(AbstractAsyncConsumer::class);
        $consumer->expects($this->once())
            ->method('confirmMessage')
            ->with(
                $this->isInstanceOf(EndpointInterface::class),
                $this->equalTo($message));

        $callback = $consumer->callback($this->createMock(EndpointInterface::class));

        $callback($message);
    }

    /**
     * Test that messages are not confirmed if an exception is thrown during processing.
     */
    public function testMessageIsNotConfirmedAfterFailedProcessing()
    {
        /** @var AbstractAsyncConsumer|MockObject $consumer */
        $consumer = $this->getMockForAbstractClass(AbstractAsyncConsumer::class);
        $consumer->expects($this->never())
            ->method('confirmMessage');

        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint->expects($this->once())
            ->method('handle')
            ->willThrowException(new \RuntimeException());

        $callback = $consumer->callback($endpoint);

        $this->expectException(\RuntimeException::class);

        $callback(new QueueMessage());
    }

    /**
     * Test no exceptions are re thrown if the consumer is stopped.
     */
    public function testConsumerWillNotThrowExceptionIfItHasBeenStopped()
    {
        /** @var AbstractAsyncConsumer|MockObject $consumer */
        $consumer = $this->getMockForAbstractClass(AbstractAsyncConsumer::class);
        $consumer->expects($this->once())
            ->method('waitNoBlock')
            ->willReturnCallback(function () use ($consumer) {
                // Stop consumer
                $consumer->stop();
                // Simulate a processing error
                throw new \RuntimeException('If you see this, the test failed. Exception should not be rethrown if the consumer was told to stop.');
            });

        $consumer->consume($this->createMock(EndpointInterface::class));
    }
}
