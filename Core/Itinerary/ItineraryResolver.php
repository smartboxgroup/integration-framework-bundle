<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Itinerary;

use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouterResourceNotFound;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesItinerariesRouter;

class ItineraryResolver
{
    use UsesItinerariesRouter;

    public function getItinerary($from, $version){
        $params = $this->getItineraryParams($from, $version);

        return $params[InternalRouter::KEY_ITINERARY];
    }

    /**
     * @param $uri
     * @param $version
     * @return string
     */
    public static function getItineraryURIWithVersion($uri, $version){
        return 'v' . $version . '-' . $uri;
    }

    /**
     * @param $from
     * @param $version
     * @return array
     * @throws \Exception
     */
    public function getItineraryParams($from, $version){

        $from = self::getItineraryURIWithVersion($from,$version);

        // Find itinerary
        try {
            $params = $this->getItinerariesRouter()->match($from);
        } catch (InternalRouterResourceNotFound $exception) {
            throw new InternalRouterResourceNotFound("Itinerary not found for uri: $from");
        }
        if (empty($params) || !array_key_exists(InternalRouter::KEY_ITINERARY, $params)) {
            throw new InternalRouterResourceNotFound("Itinerary not found for uri: $from");
        }

        $itinerary = $params[InternalRouter::KEY_ITINERARY];
        if (!$itinerary instanceof Itinerary){
            throw new \Exception("Error trying to get itinerary for '$from', the itinerary must be an instance of Itinerary.");
        }

        return $params;
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