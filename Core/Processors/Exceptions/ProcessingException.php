<?php
namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;

/**
 * Class ProcessingException
 *
 * @package Smartbox\Integration\FrameworkBundle\Exceptions
 */
class ProcessingException extends \Exception implements SerializableInterface {
    use HasInternalType;

    /**
     * @var \Exception
     * @JMS\Expose
     * @JMS\Type("Exception")
     * @JMS\Groups({"logs"})
     */
    protected $originalException;

    /**
     * @var Exchange
     * @JMS\Expose
     * @JMS\Type("Exchange")
     * @JMS\Groups({"logs"})
     */
    protected $exchange;

    /**
     * @var Processor
     * @JMS\Expose
     * @JMS\Type("Processor")
     * @JMS\Groups({"logs"})
     */
    protected $processor;

    /**
     * @var SerializableArray
     * @JMS\Expose
     * @JMS\Type("SerializableArray")
     * @JMS\Groups({"logs"})
     */
    protected $processingContext;

    /**
     * @return \Exception
     */
    public function getOriginalException()
    {
        return $this->originalException;
    }

    /**
     * @param \Exception $originalException
     */
    public function setOriginalException($originalException)
    {
        $this->originalException = $originalException;
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
}
