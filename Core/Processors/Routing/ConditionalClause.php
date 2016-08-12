<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Routing;

use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;

/**
 * Class ConditionalClause.
 */
class ConditionalClause
{
    /** @var string */
    protected $condition;

    /** @var  Itinerary */
    protected $itinerary;

    /**
     * @param string         $condition
     * @param Itinerary|null $itinerary
     */
    public function __construct($condition = null, Itinerary $itinerary = null)
    {
        if (!(is_string($condition) || is_null($condition))) {
            throw new \InvalidArgumentException('Expected string as a first argument');
        }

        $this->condition = $condition;
        $this->itinerary = $itinerary;
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;
    }

    /**
     * @return Itinerary
     */
    public function getItinerary()
    {
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
