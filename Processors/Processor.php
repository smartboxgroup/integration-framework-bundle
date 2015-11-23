<?php

namespace Smartbox\Integration\FrameworkBundle\Processors;

use Smartbox\Integration\FrameworkBundle\Events\ProcessEvent;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidMessageException;
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

    protected abstract function doProcess(Exchange $exchange);

    protected function triggerPreProcess(Exchange $exchange)
    {
        $event = new ProcessEvent(ProcessEvent::TYPE_BEFORE);
        $event->setProcessor($this);
        $event->setExchange($exchange);
        $this->getEventDispatcher()->dispatch(ProcessEvent::TYPE_BEFORE, $event);

        return;
    }

    protected function triggerPostProcess(Exchange $exchange)
    {
        $event = new ProcessEvent(ProcessEvent::TYPE_AFTER);
        $event->setProcessor($this);
        $event->setExchange($exchange);
        $this->getEventDispatcher()->dispatch(ProcessEvent::TYPE_AFTER, $event);

        return;
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
     * @throws InvalidMessageException
     */
    public function process(Exchange $exchange)
    {
        // Pre process event
        $this->triggerPreProcess($exchange);

        // Process
        $res = $this->doProcess($exchange);

        // Post process event
        $this->triggerPostProcess($exchange);

        return $res;
    }
}
