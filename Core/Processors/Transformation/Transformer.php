<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Transformation;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;

/**
 * Class Transformer.
 */
class Transformer extends Processor
{
    use UsesEvaluator;

    /**
     * @var string
     */
    protected $expression;

    /**
     * @param string $expression
     */
    public function setExpression($expression)
    {
        $this->expression = $expression;
    }

    /**
     * @param Exchange          $exchange
     * @param SerializableArray $processingContext
     *
     * @return bool
     */
    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $evaluator = $this->getEvaluator();

        try {
            $evaluator->evaluateWithExchange($this->expression, $exchange);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                'Transformer could not evaluate expression: "'.$this->expression.'". '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
