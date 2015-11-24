<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions;


use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ProcessEvent
 * @package Smartbox\Integration\FrameworkBundle\Events
 */
class HandlerException extends \Exception{

    /**
     * @var  MessageInterface
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Messages\MessageInterface")
     * @JMS\Groups({"logs"})
     */
    protected $failedMessage;

    /**
     * @return mixed
     */
    public function getFailedMessage()
    {
        return $this->failedMessage;
    }

    /**
     * @param mixed $failedMessage
     */
    public function setFailedMessage($failedMessage)
    {
        $this->failedMessage = $failedMessage;
    }

    public function __construct($message = "",MessageInterface $failed){
        parent::__construct($message);
        $this->failedMessage = $failed;
    }

}