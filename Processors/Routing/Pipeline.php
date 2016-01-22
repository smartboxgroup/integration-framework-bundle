<?php

namespace Smartbox\Integration\FrameworkBundle\Processors\Routing;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Processors\Itinerary;
use Smartbox\Integration\FrameworkBundle\Processors\Processor;

/**
 * Class Pipeline
 * @package Smartbox\Integration\FrameworkBundle\Processors\Routing
 */
class Pipeline extends Processor
{
    /**
     * @var Itinerary
     */
    protected $itinerary;

    /**
     * @return Itinerary
     */
    public function getItinerary()
    {
        return $this->itinerary;
    }

    /**
     * @param Itinerary $itinerary
     */
    public function setItinerary(Itinerary $itinerary)
    {
        $this->itinerary = $itinerary;
    }

    /**
     * @param Exchange $mainExchange
     */
    protected function doProcess(Exchange $mainExchange, SerializableArray $processingContext)
    {
        $mainExchange->getItinerary()->prepend($this->getItinerary());
    }
}
