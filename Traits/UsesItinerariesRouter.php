<?php

namespace Smartbox\Integration\FrameworkBundle\Traits;


use Smartbox\Integration\FrameworkBundle\Processors\Itinerary;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

trait UsesItinerariesRouter {

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

    public function findItineraryParams($from){

        // Find itinerary
        try{
            $params = $this->getItinerariesRouter()->match($from);
        }catch (ResourceNotFoundException $exception){
            throw new RouteNotFoundException("Itinerary not found for uri: $from");
        }
        if(empty($params || !array_key_exists(InternalRouter::KEY_ITINERARY,$params))){
            throw new RouteNotFoundException("Itinerary not found for uri: $from");
        }

        $itinerary = $params[InternalRouter::KEY_ITINERARY];
        if(!$itinerary instanceof Itinerary){
            throw new \Exception("Error trying to get itinerary for '$from', the itinerary must be an instance of Itinerary.");
        }

        return $params;
    }


    /**
     * Returns the parameters that should be propagated throught the route using the exchange headers
     * @param $params
     * @return array
     */
    public function filterItineraryParamsToPropagate($params){
        $res = [];

        foreach ($params as $key => $value) {
            if(!empty($value) && is_string($value)){
                $res[$key] = $value;
            }
        }

        return $res;
    }

}