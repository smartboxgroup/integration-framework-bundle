<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Producers;

use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointUnrecoverableException;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesItinerariesRouter;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class DirectProducer
 * @package Smartbox\Integration\FrameworkBundle\Core\Producers
 */
class DirectProducer extends Producer
{
    use UsesItinerariesRouter;


    /** {@inheritDoc} */
    public function send(Exchange $ex, EndpointInterface $endpoint)
    {
        if(!$endpoint->getURI()){
            throw new EndpointUnrecoverableException("URI not found Endpoint");
        }

        $uri = $endpoint->getURI();
        $itineraryParams = $this->findItineraryParams($uri);
        $itinerary = $itineraryParams[InternalRouter::KEY_ITINERARY];
        $headersToPropagate = $this->filterItineraryParamsToPropagate($itineraryParams);

        // Update exchange
        $ex->getItinerary()->prepend($itinerary);
        foreach ($headersToPropagate as $key => $value) {
            $ex->setHeader($key,$value);
        }
    }
}