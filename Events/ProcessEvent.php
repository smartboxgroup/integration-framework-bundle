<?php

namespace Smartbox\Integration\FrameworkBundle\Events;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Processors\Processor;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ProcessEvent
 * @package Smartbox\Integration\FrameworkBundle\Events
 */
class ProcessEvent extends Event
{
    /** Event type constants */
    const TYPE_BEFORE = "smartesb.process.before_process";
    const TYPE_AFTER = "smartesb.process.after_process";

    /**
     * @var Processor
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Processors\Processor")
     */
    protected $processor;

    /**
     * @var Exchange
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Messages\Exchange")
     */
    protected $exchange;

    /**
     * @var SerializableArray
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("Smartbox\CoreBundle\Type\SerializableArray")
     */
    protected $processingContext;

    /**
     * @return SerializableArray
     */
    public function getProcessingContext()
    {
        return $this->processingContext;
    }

    /**
     * @param SerializableArray $processingContext
     */
    public function setProcessingContext($processingContext)
    {
        $this->processingContext = $processingContext;
    }

    /**
     * @return Processor
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * @param Processor $processor
     */
    public function setProcessor($processor)
    {
        $this->processor = $processor;
    }

    /**
     * @return Exchange
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @param Exchange $exchange
     */
    public function setExchange($exchange)
    {
        $this->exchange = $exchange;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return sha1($this->processor->getId()."_".$this->exchange->getId().'_'.$this->type);
    }
}
