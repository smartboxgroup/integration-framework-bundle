<?php

namespace Smartbox\Integration\FrameworkBundle\Processors\Routing;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Processors\Itinerary;
use Smartbox\Integration\FrameworkBundle\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEvaluator;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ContentRouter
 * @package Smartbox\Integration\FrameworkBundle\Processors\Routing
 */
class ContentRouter extends Processor
{
    use UsesEvaluator;

    /**
     * @var WhenClause[]
     */
    protected $clauses = array();

    /**
     * Itinerary to use if none of the conditions on $paths is matched
     * @var Itinerary
     */
    protected $fallbackItinerary;

    /**
     * @param string|null $condition
     * @param Itinerary|null $itinerary
     */
    public function addWhen($condition = null, Itinerary $itinerary = null)
    {
        $this->clauses[] = new WhenClause($condition, $itinerary);
    }

    public function setOtherwise(Itinerary $fallback)
    {
        $this->fallbackItinerary = $fallback;
    }

    /**
     * The content router will evaluate the list of when clauses for the given exchange until one of them returns true,
     * then the itinerary of the exchange will be updated with the one specified for the when clause
     *
     * In case that no when clause matches, if there is a fallbackItinerary defined, the exchange will be updated with it,
     * otherwise nothing will happen.
     *
     * todo: Check what should be the behaviour when no when clause is matched and no fallbackItinerary is defined
     *
     * @param Exchange $exchange
     * @return bool
     */
    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $evaluator = $this->getEvaluator();

        foreach ($this->clauses as $clause) {
            if ($evaluator->evaluate($clause->getCondition(), array('msg' => $exchange->getIn())) === true) {
                $exchange->getItinerary()->prepend($clause->getItinerary());

                return true;
            }
        }

        if ($this->fallbackItinerary) {
            $exchange->getItinerary()->prepend($this->fallbackItinerary);

            return true;
        }

        return false;
    }
}
