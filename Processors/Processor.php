<?php

namespace Smartbox\Integration\FrameworkBundle\Processors;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Events\ProcessEvent;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidMessageException;
use Smartbox\Integration\FrameworkBundle\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEventDispatcher;
use Smartbox\Integration\FrameworkBundle\Traits\UsesValidator;
use Smartbox\Integration\FrameworkBundle\Service;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Processor
 * @package Smartbox\Integration\FrameworkBundle\Processors
 */
abstract class Processor extends Service implements ProcessorInterface
{
    const TYPE = "Processor";

    use UsesValidator;
    use UsesEventDispatcher;

    /**
     * @var string
     *
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\Groups({"logs"})
     */
    protected $description = "";

    protected abstract function doProcess(Exchange $exchange, SerializableArray $processingContext);

    protected function preProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $event = new ProcessEvent(ProcessEvent::TYPE_BEFORE);
        $event->setProcessor($this);
        $event->setExchange($exchange);
        $event->setProcessingContext($processingContext);
        $this->getEventDispatcher()->dispatch(ProcessEvent::TYPE_BEFORE, $event);
    }

    protected function postProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $event = new ProcessEvent(ProcessEvent::TYPE_AFTER);
        $event->setProcessor($this);
        $event->setExchange($exchange);
        $event->setProcessingContext($processingContext);
        $this->getEventDispatcher()->dispatch(ProcessEvent::TYPE_AFTER, $event);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param Exchange $exchange
     * @return bool
     * @throws ProcessingException
     */
    public function process(Exchange $exchange)
    {
        $processingContext = new SerializableArray();

        try{
            // Pre process event
            $this->preProcess($exchange,$processingContext);

            // Process
            $res = $this->doProcess($exchange, $processingContext);

            // Post process event
            $this->postProcess($exchange, $processingContext);

        }catch (\Exception $ex){
            $processingException =  new ProcessingException();
            $processingException->setProcessingContext($processingContext);
            $processingException->setExchange($exchange);
            $processingException->setOriginalException($ex);
            $processingException->setProcessor($this);

            throw $processingException;
        }

        return $res;
    }
}
