<?php

namespace Smartbox\Integration\FrameworkBundle\Processors\Transformation;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEvaluator;

/**
 * Class Transformer
 * @package Smartbox\Integration\FrameworkBundle\Processors\Transformation
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
     * @param Exchange $exchange
     * @return bool
     */
    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $evaluator = $this->getEvaluator();

        $msgCopy = unserialize(serialize($exchange->getIn()));

        try {
            $evaluator->evaluate($this->expression, array('msg' => $msgCopy));
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                'Transformer could not evaluate expression: "' . $this->expression . '". ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        $exchange->setOut($msgCopy);
    }
}
