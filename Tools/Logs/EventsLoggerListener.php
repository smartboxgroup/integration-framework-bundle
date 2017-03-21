<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Logs;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Processors\EndpointProcessor;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Events\Event;
use Smartbox\Integration\FrameworkBundle\Events\ExternalSystemHTTPEvent;
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

    /** @var RequestStack */
    protected $requestStack;

    protected static $availableEventsLogLevel = [
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    protected static $availableErrorsLogLevel = [
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
    protected $eventsLogLevel = self::DEFAULT_EVENTS_LEVEL;

    /**
     * @var string
     */
    protected $errorsLogLevel = self::DEFAULT_ERRORS_LEVEL;

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
    public static function getAvailableEventsLogLevel()
    {
        return self::$availableEventsLogLevel;
    }

    /**
     * @return array
     */
    public static function getAvailableErrorsLogLevel()
    {
        return self::$availableErrorsLogLevel;
    }

    /**
     * @param $eventsLogLevel
     *
     * @throws \InvalidArgumentException
     */
    public function setEventsLogLevel($eventsLogLevel)
    {
        if (!in_array($eventsLogLevel, $this->getAvailableEventsLogLevel())) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported events log level "%s". Use one of supported log levels: [%s].',
                    $eventsLogLevel,
                    implode(', ', $this->getAvailableEventsLogLevel())
                )
            );
        }

        $this->eventsLogLevel = $eventsLogLevel;
    }

    /**
     * @param $errorsLogLevel
     *
     * @throws \InvalidArgumentException
     */
    public function setErrorsLogLevel($errorsLogLevel)
    {
        if (!in_array($errorsLogLevel, $this->getAvailableErrorsLogLevel())) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported errors log level "%s". Use one of supported log levels: [%s].',
                    $errorsLogLevel,
                    implode(', ', $this->getAvailableErrorsLogLevel())
                )
            );
        }

        $this->errorsLogLevel = $errorsLogLevel;
    }

    public function getLogLevelForEvent(Event $event){
        if($event instanceof ProcessingErrorEvent){
            return self::DEFAULT_ERRORS_LEVEL;
        }else{
            return self::DEFAULT_EVENTS_LEVEL;
        }
    }

    /**
     * @param Event $event
     */
    public function onEvent(Event $event)
    {
        $logLevel = $this->getLogLevelForEvent($event);

        if ($event instanceof ProcessingErrorEvent) {
            $event->setRequestStack($this->requestStack);
            $message = 'Exception: '.$event->getException()->getMessage();
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
            'event_name' => $event->getEventName(),
            'event_details' => $event->getEventDetails(),
        ];

        if ($event instanceof ExternalSystemHTTPEvent) {
            $context['http_uri'] = $event->getHttpURI();
            $context['request_body'] = $event->getRequestHttpBody();
            $context['request_headers'] = $event->getRequestHttpHeaders();
            $context['response_body'] = $event->getResponseHttpBody();
            $context['response_headers'] = $event->getResponseHttpHeaders();
            $context['status'] = $event->getStatus();
            $context['exchange'] = [
                'id' => $event->getExchangeId()
            ];
            $context['transaction'] = [
                'id' => $event->getTransactionId(),
                'uri' => $event->getFromUri()
            ];
        } elseif (
            $event instanceof HandlerEvent ||
            $event instanceof ProcessEvent
        ) {
            $contextExchangeDetails = $event->getExchange();
            $context['exchange'] = [
                'id' => $contextExchangeDetails->getId(),
                'uri' => $contextExchangeDetails->getHeader('from'),
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
            'id' => $processor->getId(),
            'name' => get_class($processor),
            'description' => $processor->getDescription(),
        ];
    }
}
