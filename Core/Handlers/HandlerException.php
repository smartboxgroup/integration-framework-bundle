<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Handlers;

use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

/**
 * Class ProcessEvent.
 */
class HandlerException extends \Exception
{
    /**
     * @var MessageInterface
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface")
     * @JMS\Groups({"logs"})
     */
    protected $failedMessage;

    /**
     * HandlerException constructor.
     *
     * @param string           $message
     * @param MessageInterface $failed
     */
    public function __construct($message = '', MessageInterface $failed)
    {
        parent::__construct($message);
        $this->failedMessage = $failed;
    }

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
}
