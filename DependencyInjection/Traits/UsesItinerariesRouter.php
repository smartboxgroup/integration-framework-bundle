<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouterResourceNotFound;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;

/**
 * Trait UsesItinerariesRouter.
 */
trait UsesItinerariesRouter
{
    /** @var  InternalRouter */
    protected $itinerariesRouter;

    /**
     * @return InternalRouter
     */
    public function getItinerariesRouter()
    {
        return $this->itinerariesRouter;
    }

    /**
     * @param InternalRouter $itinerariesRouter
     */
    public function setItinerariesRouter($itinerariesRouter)
    {
        $this->itinerariesRouter = $itinerariesRouter;
    }

    /**
     * @param string $from
     *
     * @return array
     *
     * @throws \Exception
     */
    public function findItineraryParams($from)
    {
        // Find itinerary
        try {
            $params = $this->getItinerariesRouter()->match($from);
        } catch (InternalRouterResourceNotFound $exception) {
            throw new InternalRouterResourceNotFound(
                "Itinerary not found for uri: $from",
                $exception->getCode(),
                $exception
            );
        }
        if (empty($params) || !array_key_exists(InternalRouter::KEY_ITINERARY, $params)) {
            throw new InternalRouterResourceNotFound("Itinerary not found for uri: $from");
        }

        $itinerary = $params[InternalRouter::KEY_ITINERARY];
        if (!$itinerary instanceof Itinerary) {
            throw new \Exception("Error trying to get itinerary for '$from', the itinerary must be an instance of Itinerary.");
        }

        return $params;
    }

    /**
     * @param string $from
     *
     * @return \Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary
     *
     * @throws \Exception
     */
    public function getItineraryForURI($from)
    {
        $params = $this->findItineraryParams($from);

        return $params[InternalRouter::KEY_ITINERARY];
    }

    /**
     * Returns the parameters that should be propagated through the route using the exchange headers.
     *
     * @param $params
     *
     * @return array
     */
    public function filterItineraryParamsToPropagate($params)
    {
        $res = [];

        foreach ($params as $key => $value) {
            if (!empty($value) && is_string($value)) {
                $res[$key] = $value;
            }
        }

        return $res;
    }
}
