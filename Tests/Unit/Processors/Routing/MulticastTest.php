<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Processors\Routing;

use Smartbox\Integration\FrameworkBundle\Events\NewExchangeEvent;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Processors\Itinerary;
use Smartbox\Integration\FrameworkBundle\Processors\Routing\Multicast;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class MulticastTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Processors\Routing
 */
class MulticastTest extends \PHPUnit_Framework_TestCase
{
    /** @var Multicast */
    private $multicast;

    public function setUp()
    {
        $this->multicast = new Multicast();
    }

    public function testItShouldSetAndGetItineraries()
    {
        $itineraries = [
            $this->getMock(Itinerary::class),
            $this->getMock(Itinerary::class),
        ];

        $this->multicast->setItineraries($itineraries);
        $this->assertEquals($itineraries, $this->multicast->getItineraries());
    }

    public function testItShouldAddItineraries()
    {
        $this->assertEmpty($this->multicast->getItineraries());
        /** @var Itinerary $itinerary */
        $itinerary = $this->getMock(Itinerary::class);
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
        $this->setExpectedException(\InvalidArgumentException::class);

        $strategy = 'unsupported aggregation strategy';
        $this->multicast->setAggregationStrategy($strategy);
    }

    public function testItShouldDispatchAnEventForEveryItineraryOnProcess()
    {
        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcher */
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        /** @var Exchange $exchange */
        $exchange = $this->getMockBuilder(Exchange::class)->getMock();

        $itineraries = [
            $this->getMock(Itinerary::class),
            $this->getMock(Itinerary::class),
            $this->getMock(Itinerary::class),
        ];

        $this->multicast->setEventDispatcher($eventDispatcher);
        $this->multicast->setItineraries($itineraries);


        $dispatchedEventsCounter = 0;

        $eventDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->with($this->callback(function($eventName) use (&$dispatchedEventsCounter){
                if ($eventName === NewExchangeEvent::TYPE_NEW_EXCHANGE_EVENT) {
                    $dispatchedEventsCounter++;
                }

                return true;
            }), $this->anything())
        ;

        $this->multicast->process($exchange);

        $this->assertEquals(count($itineraries), $dispatchedEventsCounter);
    }

}
