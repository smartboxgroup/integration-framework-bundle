<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Routing;


use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Events\NewExchangeEvent;

/**
 * Class Multicast
 * @package Smartbox\Integration\FrameworkBundle\Core\Processors\Routing
 */
class Multicast extends Processor
{
    const AGGREGATION_STRATEGY_FIRE_AND_FORGET = 'fireAndForget';

    /**
     * @var string
     */
    protected $aggregationStrategy = self::AGGREGATION_STRATEGY_FIRE_AND_FORGET;

    /**
     * @var array
     */
    protected $itineraries = [];

    /**
     * Method returns array of available aggregation strategies
     * @return array
     */
    public static function getAvailableAggregationStrategies()
    {
        return [
            self::AGGREGATION_STRATEGY_FIRE_AND_FORGET,
        ];
    }

    /**
     * @return array
     */
    public function getItineraries()
    {
        return $this->itineraries;
    }

    /**
     * @param \Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary[] $itineraries
     */
    public function setItineraries(array $itineraries)
    {
        $this->itineraries = $itineraries;
    }

    /**
     * @param Itinerary $itinerary
     */
    public function addItinerary(Itinerary $itinerary){
        $this->itineraries[] = $itinerary;
    }

    /**
     * @return string
     */
    public function getAggregationStrategy()
    {
        return $this->aggregationStrategy;
    }

    /**
     * @param string $aggregationStrategy
     */
    public function setAggregationStrategy($aggregationStrategy)
    {
        if (!in_array($aggregationStrategy, self::getAvailableAggregationStrategies())) {
            throw new \InvalidArgumentException('Unsupported aggregation strategy: "' . $aggregationStrategy . '".');
        }
        $this->aggregationStrategy = $aggregationStrategy;
    }

    /**
     * The current implementation assumes the existence of only one aggregation strategy which ignores the child
     * exchanges
     *
     * @param \Smartbox\Integration\FrameworkBundle\Core\Exchange $mainExchange
     */
    protected function doProcess(Exchange $mainExchange, SerializableArray $processingContext)
    {
        foreach($this->itineraries as $itinerary){
            $exchange = new Exchange();

            // Set headers
            if(!empty($mainExchange->getHeaders())){
                $exchange->setHeaders($mainExchange->getHeaders());
            }

            $exchange->setHeader(Exchange::HEADER_HANDLER,$mainExchange->getHeader(Exchange::HEADER_HANDLER));
            $exchange->setHeader(Exchange::HEADER_PARENT_EXCHANGE,$mainExchange->getId());
            $exchange->setHeader(Exchange::HEADER_FROM,$mainExchange->getHeader(Exchange::HEADER_FROM));

            // Set Itinerary
            $exchange->getItinerary()->prepend($itinerary);
            $exchange->getItinerary()->setName("Multicast from \"".$mainExchange->getItinerary()->getName()."\"");

            // Set Message
            $msgCopy = unserialize(serialize($mainExchange->getIn()));
            $exchange->setIn($msgCopy);

            $event = new NewExchangeEvent($exchange);
            $event->setTimestampToCurrent();
            $this->eventDispatcher->dispatch(NewExchangeEvent::TYPE_NEW_EXCHANGE_EVENT,$event);
        }
    }
}
