<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Messages\Traits;

use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;

/**
 * Trait HasItinerary.
 */
trait HasItinerary
{
    /**
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary")
     * @JMS\Groups({"metadata"})
     * @JMS\Expose
     *
     * @var Itinerary
     */
    protected $itinerary;

    /**
     * @return Itinerary
     */
    public function getItinerary()
    {
        if (!$this->itinerary) {
            $this->itinerary = new Itinerary();
        }

        return $this->itinerary;
    }

    /**
     * @param Itinerary $itinerary
     */
    public function setItinerary(Itinerary $itinerary)
    {
        $this->itinerary = $itinerary;
    }
}
