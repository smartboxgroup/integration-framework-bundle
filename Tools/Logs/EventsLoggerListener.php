<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Logs;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Events\Event;
use Smartbox\Integration\FrameworkBundle\Events\HandlerEvent;
use Smartbox\Integration\FrameworkBundle\Events\ProcessEvent;
use Smartbox\Integration\FrameworkBundle\Events\ProcessingErrorEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EventsLoggerListener.
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
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param RequestStack    $requestStack
     * @param string          $logLevel
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
        if ($event instanceof ProcessingErrorEvent) {
            $event->setRequestStack($this->requestStack);
            $message = 'Exception: ' . $event->getException()->getMessage();
        } else {
            $message = sprintf('Event "%s" occurred', $event->getEventName());
        }

        $context = $this->getContext($event);

        $this->logger->log($this->logLevel, $message, $context);
    }

    /**
     * @param Event $event
     *
     * @return array
     */
    protected function getContext(Event $event)
    {
        $context = [
            'event_name'    => $event->getEventName(),
            'event_details' => $event->getEventDetails(),
        ];

        if (
            $event instanceof HandlerEvent ||
            $event instanceof ProcessEvent
        ) {
            $contextExchangeDetails = $event->getExchange();
            $context['exchange'] = [
                'id'   => $contextExchangeDetails->getId(),
                'uri'  => $contextExchangeDetails->getHeader('from'),
                'type' => ($contextExchangeDetails->getHeader('async') === true) ? 'async' : 'sync',
            ];
        }

        if ($event instanceof ProcessEvent) {
            $endpointUri = $event->getProcessingContext()->get('resolved_uri');
            if ($endpointUri) {
                $context['endpoint_uri'] = $endpointUri;
            }

            $processor = $event->getProcessor();
            $context['processor'] = $this->getProcessorInfo($processor);

            if ($processor instanceof LogsExchangeDetails) {
                $context['exchange']['detail'] = $event->getExchange();
            }
        }

        if ($event instanceof HandlerEvent) {
            $context['exchange']['detail'] = $event->getExchange();
        }

        if ($event instanceof ProcessingErrorEvent) {
            $context['exception'] = $event->getException();
        }

        return $context;
    }

    /**
     * Method to get processor information.
     *
     * @param Processor $processor
     *
     * @return array
     */
    private function getProcessorInfo(Processor $processor)
    {
        return [
            'id'          => $processor->getId(),
            'name'        => get_class($processor),
            'description' => $processor->getDescription(),
        ];
    }
}
