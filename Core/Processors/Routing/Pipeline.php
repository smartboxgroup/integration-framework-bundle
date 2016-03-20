<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Routing;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;

/**
 * Class Pipeline
 * @package Smartbox\Integration\FrameworkBundle\Core\Processors\Routing
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
     * @param \Smartbox\Integration\FrameworkBundle\Core\Exchange $mainExchange
     */
    protected function doProcess(Exchange $mainExchange, SerializableArray $processingContext)
    {
        $mainExchange->getItinerary()->prepend($this->getItinerary());
    }
}
