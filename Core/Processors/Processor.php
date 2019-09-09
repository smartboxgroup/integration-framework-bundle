<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\Events\ProcessEvent;
use Smartbox\Integration\FrameworkBundle\Service;
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
    final protected function preProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $event = $this->createProcessEvent($exchange, $processingContext, ProcessEvent::TYPE_BEFORE);
        $this->onPreProcessEvent($event);
        $this->getEventDispatcher()->dispatch(ProcessEvent::TYPE_BEFORE, $event);
    }

    /**
     * Method to customize pre process event to add more specific information.
     * This method is the entry point to do these customizations for the pre process event.
     *
     * @param ProcessEvent $event
     */
    protected function onPreProcessEvent(ProcessEvent $event)
    {
        return;
    }

    /**
     * @param Exchange          $exchange
     * @param SerializableArray $processingContext
     */
    final protected function postProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $event = $this->createProcessEvent($exchange, $processingContext, ProcessEvent::TYPE_AFTER);
        $this->onPostProcessEvent($event);
        $this->getEventDispatcher()->dispatch(ProcessEvent::TYPE_AFTER, $event);
    }

    /**
     * Method to customize post process event to add more specific information.
     * This method is the entry point to do these customizations for the post process event.
     *
     * @param ProcessEvent $event
     */
    protected function onPostProcessEvent(ProcessEvent $event)
    {
        return;
    }

    /**
     * Method to create a process event.
     *
     * @param Exchange          $exchange
     * @param SerializableArray $processingContext
     * @param string            $type              Event type
     *
     * @return ProcessEvent
     */
    final protected function createProcessEvent(Exchange $exchange, SerializableArray $processingContext, $type)
    {
        $event = new ProcessEvent($type);
        $event->setId(uniqid('', true));
        $event->setTimestampToCurrent();
        $event->setProcessor($this);
        $event->setExchange($exchange);
        $event->setProcessingContext($processingContext);

        return $event;
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
     * @throws ProcessingException
     */
    final public function process(Exchange $exchange)
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
