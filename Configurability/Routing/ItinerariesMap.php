<?php

namespace Smartbox\Integration\FrameworkBundle\Configurability\Routing;


use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This class stores a map of itineraries URI -> Itinerary Id
 *
 * Class ItinerariesMap
 * @package Smartbox\Integration\FrameworkBundle\Configurability\Routing
 */
class ItinerariesMap {

    /**
     * @var array
     */
    protected $itineraries = [];

    /**
     * @return array
     */
    public function getItineraries()
    {
        return $this->itineraries;
    }

    /**
     * @param array $itineraries
     */
    public function setItineraries($itineraries)
    {
        $this->itineraries = $itineraries;
    }

    public function addItinerary($from,$itineraryRef){
        if(array_key_exists($from,$this->itineraries)){
           throw new InvalidConfigurationException("From URI used twice for different itineraries");
        }

        $this->itineraries[$from] = (string) $itineraryRef;
    }
}