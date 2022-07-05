<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Routing;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\Events\ProcessEvent;

/**
 * Class ContentRouter.
 */
class ContentRouter extends Processor
{
    use UsesEvaluator;

    /**
     * @var string
     */
    const CONDITION_MATCHED = 'condition_matched';

    /**
     * @var ConditionalClause[]
     */
    protected $clauses = [];

    /**
     * Itinerary to use if none of the conditions on $paths is matched.
     *
     * @var Itinerary
     */
    protected $fallbackItinerary;

    /**
     * @param string|null    $condition
     * @param Itinerary|null $itinerary
     */
    public function addWhen($condition = null, Itinerary $itinerary = null)
    {
        $this->clauses[] = new ConditionalClause($condition, $itinerary);
    }

    public function setOtherwise(Itinerary $fallback)
    {
        $this->fallbackItinerary = $fallback;
    }

    /**
     * The content router will evaluate the list of when clauses for the given exchange until one of them returns true,
     * then the itinerary of the exchange will be updated with the one specified for the when clause.
     *
     * In case that no when clause matches, if there is a fallbackItinerary defined, the exchange will be updated with it,
     * otherwise nothing will happen.
     *
     * todo: Check what should be the behaviour when no when clause is matched and no fallbackItinerary is defined
     *
     * @param Exchange $exchange
     *
     * @return bool
     */
    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $evaluator = $this->getEvaluator();

        foreach ($this->clauses as $clause) {
            if ($evaluator->evaluateWithExchange($clause->getCondition(), $exchange)) {
                $exchange->getItinerary()->prepend($clause->getItinerary());
                $processingContext->set(self::CONDITION_MATCHED, $clause->getCondition());

                return true;
            }
        }

        if ($this->fallbackItinerary) {
            $exchange->getItinerary()->prepend($this->fallbackItinerary);
            $processingContext->set(self::CONDITION_MATCHED, 'fallback itinerary');

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function onPostProcessEvent(ProcessEvent $event)
    {
        $condition = $event->getProcessingContext()->get(self::CONDITION_MATCHED);
        $event->setEventDetails('Matched condition: '.$condition);
    }

    /**
     * {@inheritdoc}
     */
    public function getClauses(): array
    {
        return $this->clauses;
    }
}
