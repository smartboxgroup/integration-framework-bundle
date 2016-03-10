<?php

namespace Smartbox\Integration\FrameworkBundle\EventListener;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Smartbox\Integration\FrameworkBundle\Events\Error\ProcessingErrorEvent;
use Smartbox\Integration\FrameworkBundle\Events\Event;
use Smartbox\Integration\FrameworkBundle\Events\ProcessEvent;
use Smartbox\Integration\FrameworkBundle\Processors\Endpoint;
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
            $message = 'Exception: ' . $event->getException()->getMessage();
        } else {
            $message = sprintf('Event "%s" occurred', $event->getEventName());
        }

        $context = ['event_details' => $event];

        $transactionContextDetails = $event->getExchange()->getIn()->getContext();
        $context['transaction'] = [
            'id' => $transactionContextDetails->get('transaction_id'),
            'user' => $transactionContextDetails->get('user'),
            'ip' => $transactionContextDetails->get('ip'),
            'uri' => $transactionContextDetails->get('from'),
            'timestamp' => $transactionContextDetails->get('timestamp'),
        ];

        $transactionHeadersDetails = $event->getExchange();
        $context['exchange'] = [
            'id' => $transactionHeadersDetails->getId(),
            'uri' => $transactionHeadersDetails->getHeader('from'),
            'type' => ($transactionHeadersDetails->getHeader('async') === true)? 'async' : 'sync',
        ];

        if ($event instanceof ProcessEvent) {
            $endpointUri = $event->getProcessingContext()->get('resolved_uri');
            if ($endpointUri) {
                $context['endpoint_uri'] = $endpointUri;
            }
        }

        if($event instanceof ProcessingErrorEvent){
            $context['exception'] = $event->getException();
        }

        $this->logger->log($this->logLevel, $message, $context);
    }
}
