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
    const DEFAULT_EVENTS_LEVEL = LogLevel::DEBUG;
    const DEFAULT_ERRORS_LEVEL = LogLevel::ERROR;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /** @var  RequestStack */
    protected $requestStack;

    protected static $eventsLogLevelOptions = [
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    protected static $errorsLogLevelOptions = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::DEBUG,
    ];

    /**
     * @var string
     */
    protected $eventsLogLevel = LogLevel::DEBUG;

    /**
     * @var string
     */
    protected $errorsLogLevel = LogLevel::ERROR;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param RequestStack    $requestStack
     */
    public function __construct(LoggerInterface $logger, RequestStack $requestStack)
    {
        $this->logger = $logger;
        $this->requestStack = $requestStack;
    }

    /**
     * @return array
     */
    public static function getEventsLogLevelOptions()
    {
        return self::$eventsLogLevelOptions;
    }

    /**
     * @return array
     */
    public static function getErrorsLogLevelOptions()
    {
        return self::$errorsLogLevelOptions;
    }

    /**
     * @param $eventsLogLevel
     */
    public function setEventsLogLevel($eventsLogLevel)
    {
        $this->eventsLogLevel = $eventsLogLevel;
    }

    /**
     * @param $errorsLogLevel
     */
    public function setErrorsLogLevel($errorsLogLevel)
    {
        $this->errorsLogLevel = $errorsLogLevel;
    }

    /**
     * @param Event $event
     */
    public function onEvent(Event $event)
    {
        $logLevel = $this->eventsLogLevel;
        if ($event instanceof ProcessingErrorEvent) {
            $event->setRequestStack($this->requestStack);
            $message = 'Exception: ' . $event->getException()->getMessage();
            $logLevel = $this->errorsLogLevel;
        } else {
            $message = sprintf('Event "%s" occurred', $event->getEventName());
        }

        $context = $this->getContext($event);

        $this->logger->log($logLevel, $message, $context);
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
