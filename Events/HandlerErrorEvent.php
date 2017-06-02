<?php

namespace Smartbox\Integration\FrameworkBundle\Events;

use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ProcessingErrorEvent.
 */
class HandlerErrorEvent extends HandlerEvent
{
    const EVENT_NAME = 'smartesb.handler.error';

    /**
     * @JMS\Expose
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $id;

    /**
     * @var \Exception
     * @JMS\Type("Exception")
     * @JMS\Groups({"logs"})
     * @JMS\Expose
     */
    protected $exception;

    /**
     * @var RequestStack
     * @JMS\Type("Symfony\Component\HttpFoundation\RequestStack")
     * @JMS\Groups({"logs"})
     * @JMS\Expose
     */
    protected $requestStack;

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param \Exception $exception
     */
    public function setException($exception)
    {
        $this->exception = $exception;
    }

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
     * @return RequestStack
     */
    public function getRequestStack()
    {
        return $this->requestStack;
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack($requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return mixed
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @param mixed $exchange
     */
    public function setExchange($exchange)
    {
        $this->exchange = $exchange;
    }

    /***
     * @param Exchange $exchange
     * @param \Exception $exception
     * @param string $eventName
     */
    public function __construct(
        Exchange $exchange,
        \Exception $exception,
        $eventName = self::EVENT_NAME
    ) {
        parent::__construct($eventName);
        $this->exchange = $exchange;
        $this->exception = $exception;
    }

}
