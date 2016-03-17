<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions;

use JMS\Serializer\Annotation as JMS;

/**
 * Class InvalidOptionException
 * @package Smartbox\Integration\FrameworkBundle\Exceptions
 */
class InvalidOptionException extends \Exception
{
    /**
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\Groups({"logs"})
     * @var string
     */
    protected $producerClass;

    /**
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\Groups({"logs"})
     * @var string
     */
    protected $fieldName;

    /**
     * Constructor
     *
     * @param string $producerClass
     * @param string $fieldName
     * @param string|null $message
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct(
        $producerClass,
        $fieldName,
        $message = null,
        $code = 0,
        \Exception $previous = null
    ) {
        $this->producerClass = $producerClass;
        $this->fieldName = $fieldName;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getProducerClass()
    {
        return $this->producerClass;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }
}
