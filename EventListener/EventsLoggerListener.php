<?php

namespace Smartbox\Integration\FrameworkBundle\EventListener;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Smartbox\Integration\FrameworkBundle\Events\Error\ProcessingErrorEvent;
use Smartbox\Integration\FrameworkBundle\Events\Event;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EventsLoggerListener
 * @package Smartbox\Integration\FrameworkBundle\EventListener
 */
class EventsLoggerListener
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /** @var  RequestStack */
    protected $requestStack;

    /**
     * @var string
     */
    protected $logLevel;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param RequestStack $requestStack
     * @param string $logLevel
     */
    public function __construct(LoggerInterface $logger, RequestStack $requestStack, $logLevel = LogLevel::DEBUG)
    {
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->logLevel = $logLevel;
    }

    /**
     * @param Event $event
     */
    public function onEvent(Event $event)
    {
        if($event instanceof ProcessingErrorEvent){
            $event->setRequestStack($this->requestStack);
        }

        $message = sprintf('Event "%s" occurred', $event->getEventName());
        $this->logger->log($this->logLevel, $message, ['event' => $event]);
    }
}
