<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Messages\Traits;


use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Symfony\Component\Validator\Constraints as Assert;

trait HasItinerary
{
    /**
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary")
     * @JMS\Groups({"metadata"})
     * @JMS\Expose
     * @var \Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary
     */
    protected $itinerary;

    /**
     * @return \Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary
     */
    public function getItinerary()
    {
        if (!$this->itinerary) {
            $this->itinerary = new Itinerary();
        }

        return $this->itinerary;
    }

    /**
     * @param \Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary $itinerary
     */
    public function setItinerary(Itinerary $itinerary)
    {
        $this->itinerary = $itinerary;
    }

}