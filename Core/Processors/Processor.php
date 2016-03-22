<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\Events\ProcessEvent;
use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEventDispatcher;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesValidator;

/**
 * Class Processor.
 */
abstract class Processor extends Service implements ProcessorInterface
{
    const TYPE = 'Processor';
    const CONTEXT_PROCESSOR_ID = 'processor_id';
    const CONTEXT_PROCESSOR_DESCRIPTION = 'processor_description';

    use UsesValidator;
    use UsesEventDispatcher;

    /**
     * @var string
     *
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\Groups({"logs"})
     */
    protected $description = '';

    /**
     * @var bool
     */
    protected $runtimeBreakpoint = false;

    /**
     * @param Exchange          $exchange
     * @param SerializableArray $processingContext
     */
    abstract protected function doProcess(Exchange $exchange, SerializableArray $processingContext);

    /**
     * @param Exchange          $exchange
     * @param SerializableArray $processingContext
     */
    protected function preProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $event = new ProcessEvent(ProcessEvent::TYPE_BEFORE);
        $event->setTimestampToCurrent();
        $event->setProcessor($this);
        $event->setExchange($exchange);
        $event->setProcessingContext($processingContext);
        $this->getEventDispatcher()->dispatch(ProcessEvent::TYPE_BEFORE, $event);
    }

    /**
     * @param Exchange          $exchange
     * @param SerializableArray $processingContext
     */
    protected function postProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $event = new ProcessEvent(ProcessEvent::TYPE_AFTER);
        $event->setTimestampToCurrent();
        $event->setProcessor($this);
        $event->setExchange($exchange);
        $event->setProcessingContext($processingContext);
        $this->getEventDispatcher()->dispatch(ProcessEvent::TYPE_AFTER, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * {@inheritdoc}
     */
    public function setRuntimeBreakpoint($runtimeBreakpoint)
    {
        $this->runtimeBreakpoint = (bool) $runtimeBreakpoint;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ProcessingException
     */
    public function process(Exchange $exchange)
    {
        if ($this->runtimeBreakpoint && function_exists('xdebug_break')) {
            xdebug_break();
        }

        /*
         *
         * DEBUGGING HINTS
         *
         * If you add a runtime breakpoint in a processor xml definition inside a flow you will break here.
         *
         * The following function calls preProcess, doProcess and postProcess contain the real code you want to debug.
         *
         * To debug in that way you can add this to your xml flow file, as part of the processor you want to debug:
         *
         *      <... runtime-breakpoint="1"/>
         *
         */
        $processingContext = new SerializableArray();

        try {
            #
            // Pre process event
            $this->preProcess($exchange, $processingContext);

            // Process
            $res = $this->doProcess($exchange, $processingContext);

            // Post process event
            $this->postProcess($exchange, $processingContext);
        } catch (\Exception $ex) {
            $processingException = new ProcessingException();
            $processingException->setProcessingContext($processingContext);
            $processingException->setExchange($exchange);
            $processingException->setOriginalException($ex);
            $processingException->setProcessor($this);

            throw $processingException;
        }

        return $res;
    }
}
