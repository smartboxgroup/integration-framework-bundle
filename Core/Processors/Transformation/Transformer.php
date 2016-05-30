<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Transformation;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\Events\ProcessEvent;
use Smartbox\Integration\FrameworkBundle\Tools\Logs\LogsExchangeDetails;

/**
 * Class Transformer.
 */
class Transformer extends Processor implements LogsExchangeDetails
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

    /**
     * {@inheritdoc}
     */
    protected function onPreProcessEvent(ProcessEvent $event)
    {
        $event->setEventDetails('About to apply transformation: ' . $this->expression);
    }

    /**
     * {@inheritdoc}
     */
    protected function onPostProcessEvent(ProcessEvent $event)
    {
        $event->setEventDetails('Applied transformation: ' . $this->expression);
    }
}
