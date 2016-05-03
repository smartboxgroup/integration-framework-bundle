<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Core\Itinerary\ItineraryResolver;

/**
 * Trait UsesItineraryResolver.
 */
trait UsesItineraryResolver
{
    /** @var  ItineraryResolver */
    protected $itineraryResolver;

    /**
     * @return ItineraryResolver
     */
    public function getItineraryResolver()
    {
        return $this->itineraryResolver;
    }

    /**
     * @param ItineraryResolver $itinerariesRouter
     */
    public function setItineraryResolver(ItineraryResolver $itinerariesRouter)
    {
        $this->itineraryResolver = $itinerariesRouter;
    }
}
