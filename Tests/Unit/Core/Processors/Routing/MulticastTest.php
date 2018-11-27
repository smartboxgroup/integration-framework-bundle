<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Processors\Routing;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\Multicast;
use Smartbox\Integration\FrameworkBundle\Events\NewExchangeEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class MulticastTest.
 */
class MulticastTest extends \PHPUnit\Framework\TestCase
{
    /** @var Multicast */
    private $multicast;

    protected function setUp()
    {
        $this->multicast = new Multicast();
    }

    protected function tearDown()
    {
        $this->multicast = null;
    }

    public function testItShouldSetAndGetItineraries()
    {
        $itineraries = [
            $this->createMock(Itinerary::class),
            $this->createMock(Itinerary::class),
        ];

        $this->multicast->setItineraries($itineraries);
        $this->assertEquals($itineraries, $this->multicast->getItineraries());
    }

    public function testItShouldAddItineraries()
    {
        $this->assertEmpty($this->multicast->getItineraries());
        /** @var \Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary $itinerary */
        $itinerary = $this->createMock(Itinerary::class);
        $this->multicast->addItinerary($itinerary);
        $this->assertCount(1, $this->multicast->getItineraries());
    }

    public function testItShouldSetAndGetAnAggregationStrategy()
    {
        $strategy = Multicast::AGGREGATION_STRATEGY_FIRE_AND_FORGET;
        $this->multicast->setAggregationStrategy($strategy);
        $this->assertEquals($strategy, $this->multicast->getAggregationStrategy());
    }

    public function testItShouldNotSetAnUnsupportedAggregationStrategy()
    {
        $this->expectException(\InvalidArgumentException::class);

        $strategy = 'unsupported aggregation strategy';
        $this->multicast->setAggregationStrategy($strategy);
    }

    public function testItShouldDispatchAnEventForEveryItineraryOnProcess()
    {
        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcher */
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        /** @var Exchange|\PHPUnit_Framework_MockObject_MockObject $exchange */
        $exchange = $this->getMockBuilder(Exchange::class)->getMock();
        $exchange->method('getItinerary')->willReturn(new Itinerary());
        $exchange->method('getHeader')->willReturn('xxxx');
        $exchange->method('getId')->willReturn('123');

        $itineraries = [
            $this->createMock(Itinerary::class),
            $this->createMock(Itinerary::class),
            $this->createMock(Itinerary::class),
        ];

        $this->multicast->setEventDispatcher($eventDispatcher);
        $this->multicast->setItineraries($itineraries);

        $dispatchedEventsCounter = 0;

        $eventDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->with($this->callback(function ($eventName) use (&$dispatchedEventsCounter) {
                if ($eventName === NewExchangeEvent::TYPE_NEW_EXCHANGE_EVENT) {
                    ++$dispatchedEventsCounter;
                }

                return true;
            }), $this->anything())
        ;

        $this->multicast->process($exchange);

        $this->assertCount($dispatchedEventsCounter, $itineraries);
    }
}
