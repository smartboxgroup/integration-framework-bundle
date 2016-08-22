<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator;

/**
 * Trait UsesEvaluator.
 */
trait UsesEvaluator
{
    /** @var ExpressionEvaluator */
    protected $evaluator;

    /**
     * @return ExpressionEvaluator
     */
    public function getEvaluator()
    {
        return $this->evaluator;
    }

    /**
     * @param ExpressionEvaluator $evaluator
     */
    public function setEvaluator(ExpressionEvaluator $evaluator)
    {
        $this->evaluator = $evaluator;
    }
}
