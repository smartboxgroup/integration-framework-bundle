<?php

namespace Smartbox\Integration\FrameworkBundle\Events;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class MalformedInputEvent.
 */
class MalformedInputEvent extends Event
{
    const EVENT_NAME = 'smartesb.event.malformed_input_event';

    /**
     * MalformedInputEvent Constructor.
     */
    public function __construct()
    {
        parent::__construct(self::EVENT_NAME);
    }

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $transactionId;

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $message;

    /**
     * @var BadRequestHttpException
     * @JMS\Type("BadRequestHttpException")
     * @JMS\Groups({"logs"})
     * @JMS\Expose
     */
    protected $exception;

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return BadRequestHttpException
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param BadRequestHttpException $exception
     */
    public function setException($exception)
    {
        $this->exception = $exception;
    }
}
