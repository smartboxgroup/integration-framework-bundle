<?php

namespace Smartbox\Integration\FrameworkBundle\Producers;

use Smartbox\Integration\FrameworkBundle\Exceptions\producerUnrecoverableException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Traits\UsesItinerariesRouter;

/**
 * Class DirectProducer
 * @package Smartbox\Integration\FrameworkBundle\Producers
 */
class DirectProducer extends Producer
{
    /**
     * @JMS\Exclude
     * @var array
     */
    protected static $SUPPORTED_EXCHANGE_PATTERNS = [self::EXCHANGE_PATTERN_IN_OUT];

    use UsesItinerariesRouter;

    /** {@inheritDoc} */
    public function send(Exchange $ex, array $options)
    {
        if(!array_key_exists(InternalRouter::KEY_URI,$options)){
            throw new producerUnrecoverableException("URI not found in options");
        }

        $uri = $options[InternalRouter::KEY_URI];
        $itineraryParams = $this->findItineraryParams($uri);
        $itinerary = $itineraryParams[InternalRouter::KEY_ITINERARY];
        $headersToPropagate = $this->filterItineraryParamsToPropagate($itineraryParams);

        // Update exchange
        $ex->getItinerary()->prepend($itinerary);
        foreach ($headersToPropagate as $key => $value) {
            $ex->setHeader($key,$value);
        }
    }

    public function getAvailableOptions(){
        return [];
    }
}
