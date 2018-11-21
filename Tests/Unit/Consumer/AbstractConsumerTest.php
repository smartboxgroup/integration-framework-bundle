<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Consumer;

use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Events\TimingEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AbstractConsumerTest extends \PHPUnit\Framework\TestCase
{
    public function testConsumerTimeDispatched()
    {
        $handleTimeUs = 9.99 * 1000;
        $lowerBoundMs = 9;
        $upperBoundMs = 30;

        $consumer = $this->getMockForAbstractClass(AbstractConsumer::class,
            ['initialize', 'readMessage'],
            '',
            true,
            true,
            true,
            ['shouldStop']
        );

        $consumer
            ->expects($this->once())
            ->method('initialize')
            ;

        $consumer
            ->expects($this->exactly(2))
            ->method('shouldStop')
            ->willReturnOnConsecutiveCalls(false, true);

        $consumer
            ->expects($this->once())
            ->method('readMessage')
            ->willReturn(new Message());

        $eventDispatcher = $this->getMockForAbstractClass(EventDispatcherInterface::class);

        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo('smartesb.consumer.timing'), $this->callback(function ($event) use ($lowerBoundMs, $upperBoundMs) {
                $this->assertInstanceOf(TimingEvent::class, $event);
                $interval = $event->getIntervalMs();

                //here we need to allow for a small variance in the amount of time taken to 'handle' the message
                return $interval > $lowerBoundMs && $interval < $upperBoundMs;
            }))
            ;

        $consumer->setEventDispatcher($eventDispatcher);

        $endpoint = $this->createMock(Endpoint::class);
        $endpoint->expects($this->once())
            ->method('handle')
            ->will(
                $this->returnCallback(function () use ($handleTimeUs) {
                    \usleep($handleTimeUs);
                })
            )
        ;

        $consumer->consume($endpoint);
    }

    public function testDoesNotFailWhenNoDispatcher()
    {
        $consumer = $this->getMockForAbstractClass(AbstractConsumer::class);

        $class = new \ReflectionClass($consumer);
        $method = $class->getMethod('dispatchConsumerTimingEvent');
        $method->setAccessible(true);

        $method->invokeArgs($consumer, [1, new Message()]);
    }
}
