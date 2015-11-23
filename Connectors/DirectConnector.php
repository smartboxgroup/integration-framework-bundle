<?php

namespace Smartbox\Integration\FrameworkBundle\Connectors;

use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Traits\UsesItinerariesRouter;

/**
 * Class DirectConnector
 * @package Smartbox\Integration\FrameworkBundle\Connectors
 */
class DirectConnector extends Connector
{
    public static $SUPPORTED_EXCHANGE_PATTERNS = [self::EXCHANGE_PATTERN_IN_OUT];

    use UsesItinerariesRouter;

    /** {@inheritDoc} */
    public function send(Exchange $ex, array $options)
    {
        if(!array_key_exists(InternalRouter::KEY_URI,$options)){
            throw new \Exception("Connector: URI not found in options");
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
