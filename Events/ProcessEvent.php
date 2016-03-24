<?php

namespace Smartbox\Integration\FrameworkBundle\Events;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;

/**
 * Class ProcessEvent.
 */
class ProcessEvent extends Event
{
    /** Event type constants */
    const TYPE_BEFORE = 'smartesb.process.before_process';
    const TYPE_AFTER = 'smartesb.process.after_process';

    /**
     * @var Processor
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Core\Processors\Processor")
     */
    protected $processor;

    /**
     * @var Exchange
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Core\Exchange")
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
     * @param \Smartbox\Integration\FrameworkBundle\Core\Exchange $exchange
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
        return sha1($this->processor->getId().'_'.$this->exchange->getId().'_'.$this->type);
    }
}
