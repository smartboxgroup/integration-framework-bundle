<?php
namespace Smartbox\Integration\FrameworkBundle\Traits;


use Smartbox\Integration\FrameworkBundle\Util\ExpressionEvaluator;

trait UsesEvaluator
{

    /** @var  ExpressionEvaluator */
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
    public function setEvaluator($evaluator)
    {
        $this->evaluator = $evaluator;
    }

}