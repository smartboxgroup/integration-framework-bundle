<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Consumers;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractAsyncConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Events\TimingEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class AbstractAsyncConsumerTest.
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
     * Test that the consumer can be forced to leave the wait loop when a stop signal is issued.
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
     * Test that the consumer won't cause a CPU spike by usleeping x amount of microseconds if there's nothing to process.
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

        $this->assertGreaterThanOrEqual(AbstractAsyncConsumer::SLEEP_DURATION / 1000, $stopwatch->getEvent('consume')->getDuration(), 'Somehow the consumer finished faster than it should have. It should have slept between messages.');
    }

    /**
     * Test that the consumer will run as fast as possible if it's told not to sleep.
     */
    public function testConsumerDoesNotSleepWhenFlagIsSet()
    {
        $this->markTestSkipped('must be revisited.');

        $consumer = $this->getConsumer(new QueueMessage(), 2);

        $stopwatch = new Stopwatch();
        $stopwatch->start('consume');
        $consumer->consume($this->createMock(EndpointInterface::class));
        $stopwatch->stop('consume');

        $this->assertLessThanOrEqual(AbstractAsyncConsumer::SLEEP_DURATION / 1000, $stopwatch->getEvent('consume')->getDuration(), 'Consumer took longer than expected to consume a message, most likely it ignored the sleep flag.');
    }

    /**
     * Test that consumer dispatches an event with timing information.
     */
    public function testCallbackMeasuresProcessingDuration()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isType('string'),
                $this->callback(function (TimingEvent $event) {
                    return $event->getIntervalMs() >= 0;
                }));

        /** @var AbstractAsyncConsumer|MockObject $consumer */
        $consumer = $this->getConsumer(new QueueMessage(), 1);
        $consumer->setEventDispatcher($dispatcher);
        $consumer->consume($this->createMock(EndpointInterface::class));
    }

    /**
     * Test that messages are confirmed once they were successfully processed.
     */
    public function testMessageIsConfirmedAfterProcessing()
    {
        $message = new QueueMessage();
        $consumer = $this->getConsumer($message, 1);

        $consumer->expects($this->once())
            ->method('confirmMessage')
            ->with(
                $this->anything(),
                $this->equalTo($message));

        $consumer->consume($this->createMock(EndpointInterface::class));
    }

    /**
     * Test that messages are not confirmed if an exception is thrown during processing.
     */
    public function testMessageIsNotConfirmedAfterFailedProcessing()
    {
        $this->expectException(\RuntimeException::class);

        $message = new QueueMessage();
        $consumer = $this->getConsumer($message);
        $consumer->expects($this->never())
            ->method('confirmMessage');

        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint->expects($this->once())
            ->method('handle')
            ->willThrowException(new \RuntimeException());

        $consumer->consume($endpoint);
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

    /**
     * @param MessageInterface $message Message to pass to the callback
     * @param int              $rounds  Amount of messages to consume before stopping
     */
    private function getConsumer(MessageInterface $message, int $rounds = -1): MockObject
    {
        $consumer = $this->getMockForAbstractClass(AbstractAsyncConsumer::class);

        /** @var \Closure $callback */
        $callback = null;
        $consumer->expects($this->once())
            ->method('asyncConsume')
            ->with(
                $this->anything(),
                // Steal the callback so we can call it manually and pretend we are "consuming"
                $this->callback(function ($stolenCallback) use (&$callback) {
                    $callback = $stolenCallback;
                    return true;
                }));
        $consumer->expects(-1 === $rounds ? $this->any() : $this->exactly($rounds))
            ->method('waitNoBlock')
            ->willReturnCallback(function () use (&$callback, $consumer, $message) {
                $callback($message);
            });

        $consumer->setExpirationCount($rounds);

        return $consumer;
    }
}
