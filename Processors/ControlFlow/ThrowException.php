<?php

namespace Smartbox\Integration\FrameworkBundle\Processors\ControlFlow;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Exceptions\ExchangeAwareInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Processors\Processor;

/**
 * Class ThrowException
 * @package Smartbox\Integration\FrameworkBundle\Processors\ControlFlow
 */
class ThrowException extends Processor
{
    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("string")
     * @var string
     */
    protected $exceptionClass;

    /**
     * @return string
     */
    public function getExceptionClass()
    {
        return $this->exceptionClass;
    }

    /**
     * @param string $exceptionClass
     */
    public function setExceptionClass($exceptionClass)
    {
        if(     empty($exceptionClass)
            ||  !class_exists($exceptionClass)
            ||  !is_a($exceptionClass,"Exception",true)
        ){
            throw new \InvalidArgumentException("$exceptionClass is not a valid exception class");
        }

        $this->exceptionClass = $exceptionClass;
    }

    /**
     * The ThrowException will create an exception of the type specified in Ref
     * @param Exchange $exchange
     * @return bool
     * @throws ExchangeAwareInterface
     */
    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        if(empty($this->exceptionClass)){
            throw new \RuntimeException("Exception class not found");
        }

        /** @var ExchangeAwareInterface $exception */
        $exception = new $this->exceptionClass();

        throw $exception;
    }
}
