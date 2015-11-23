<?php

namespace Smartbox\Integration\FrameworkBundle\Messages\Traits;


use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Processors\Itinerary;
use Symfony\Component\Validator\Constraints as Assert;

trait HasItinerary
{
    /**
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Processors\Itinerary")
     * @JMS\Groups({"metadata"})
     * @JMS\Expose
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