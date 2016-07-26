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
     * @JMS\Expose
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $id;

    /**
     * @JMS\Expose
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Core\Processors\Processor")
     *
     * @var Processor
     */
    protected $processor;

    /**
     * @JMS\Expose
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Core\Exchange")
     *
     * @var Exchange
     */
    protected $exchange;

    /**
     * @JMS\Expose
     * @JMS\Type("Smartbox\CoreBundle\Type\SerializableArray")
     *
     * @var SerializableArray
     */
    protected $processingContext;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

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
