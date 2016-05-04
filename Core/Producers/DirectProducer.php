<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Producers;

use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointUnrecoverableException;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesItinerariesRouter;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesItineraryResolver;

/**
 * Class DirectProducer.
 */
class DirectProducer extends Producer
{
    use UsesItineraryResolver;

    /** {@inheritdoc} */
    public function send(Exchange $ex, EndpointInterface $endpoint)
    {
        if (!$endpoint->getURI()) {
            throw new EndpointUnrecoverableException('URI not found Endpoint');
        }

        $uri = $endpoint->getURI();
        $version = $ex->getIn()->getContext()->get(Context::FLOWS_VERSION);
        $itineraryParams = $this->itineraryResolver->getItineraryParams($uri,$version);
        $itinerary = $itineraryParams[InternalRouter::KEY_ITINERARY];
        $headersToPropagate = $this->itineraryResolver->filterItineraryParamsToPropagate($itineraryParams);

        // Update exchange
        $ex->getItinerary()->prepend($itinerary);
        foreach ($headersToPropagate as $key => $value) {
            $ex->setHeader($key, $value);
        }
    }
}
