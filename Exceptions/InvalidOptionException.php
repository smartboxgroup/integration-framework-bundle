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
     * @Assert\Type(type="string")
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\Groups({"logs"})
     * @var string
     */
    protected $connectorClass;

    /**
     * @Assert\Type(type="string")
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\Groups({"logs"})
     * @var string
     */
    protected $fieldName;

    /**
     * Constructor
     *
     * @param string $connectorClass
     * @param string $fieldName
     * @param string|null $message
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct(
        $connectorClass,
        $fieldName,
        $message = null,
        $code = 0,
        \Exception $previous = null
    ) {
        $this->connectorClass = $connectorClass;
        $this->fieldName = $fieldName;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getConnectorClass()
    {
        return $this->connectorClass;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }
}
