<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Handlers;

use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\UnrecoverableRestException;
use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Core\Messages\DeferredExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Messages\DelayedExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Messages\ErrorExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Messages\ExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Messages\FailedExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\RetryExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Messages\ThrottledExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\DelayException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ThrottledException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Core\Processors\ProcessorInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesItineraryResolver;
use Smartbox\Integration\FrameworkBundle\Events\HandlerErrorEvent;
use Smartbox\Integration\FrameworkBundle\Events\HandlerEvent;
use Smartbox\Integration\FrameworkBundle\Events\NewExchangeEvent;
use Smartbox\Integration\FrameworkBundle\Events\ProcessingErrorEvent;
use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface;
use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEndpointFactory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Smartbox\Integration\FrameworkBundle\Core\Messages\CallbackExchangeEnvelope;

/**
 * Class MessageHandler.
 */
class MessageHandler extends Service implements HandlerInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use UsesEndpointFactory;
    use UsesItineraryResolver;

    /** Retry delay strategy constants**/
    const RETRY_STRATEGY_FIXED = 'fixed';
    const RETRY_STRATEGY_PROGRESSIVE = 'progressive';

    /** @var int */
    protected $retriesMax;

    /** @var int */
    protected $retryDelay;

    /** @var int */
    protected $retryDelayFactor = 1;

    /** @var string */
    protected $retryStrategy;

    /**
     * @var string The URI of the endpoint that messages for retry will be send to
     */
    protected $retryURI;

    /** @var int */
    protected $throttleDelay;

    /** @var int */
    protected $throttleDelayFactor = 1;

    /** @var string */
    protected $throttleStrategy;

    /**
     * @var string The URI of the endpoint that throttle messages will be send to
     */
    protected $throttleURI;

    /** @var bool */
    protected $throwExceptions;

    /** @var bool */
    protected $deferNewExchanges;

    /** @var EndpointInterface */
    protected $failedEndpoint;

    /**
     * @var string The URI of the endpoint that messages for callback will be sent to
     */
    protected $callbackURI;

    /**
     * @return bool
     */
    public function shouldDeferNewExchanges()
    {
        return $this->deferNewExchanges;
    }

    /**
     * @param bool $deferNewExchanges
     */
    public function setDeferNewExchanges($deferNewExchanges)
    {
        $this->deferNewExchanges = $deferNewExchanges;
    }

    /**
     * @return mixed
     */
    public function getRetriesMax()
    {
        return $this->retriesMax;
    }

    /**
     * @param mixed $retriesMax
     */
    public function setRetriesMax($retriesMax)
    {
        $this->retriesMax = $retriesMax;
    }

    /**
     * @return int
     */
    public function getRetryDelay()
    {
        return $this->retryDelay;
    }

    /**
     * @param int $retryDelay
     */
    public function setRetryDelay($retryDelay)
    {
        $this->retryDelay = $retryDelay;
    }

    /**
     * @return int
     */
    public function getRetryDelayFactor()
    {
        return $this->retryDelayFactor;
    }

    /**
     * @param int $retryDelayFactor
     */
    public function setRetryDelayFactor($retryDelayFactor)
    {
        $this->retryDelayFactor = $retryDelayFactor;
    }

    /**
     * @return string
     */
    public function getRetryStrategy()
    {
        return $this->retryStrategy;
    }

    /**
     * @param string $retryStrategy
     */
    public function setRetryStrategy($retryStrategy)
    {
        $this->retryStrategy = $retryStrategy;
    }

    /**
     * Return the valid retry strategies.
     *
     * @return array
     */
    public static function getAvailableRetryStrategies()
    {
        return [
            self::RETRY_STRATEGY_FIXED,
            self::RETRY_STRATEGY_PROGRESSIVE,
        ];
    }

    /**
     * @return int
     */
    public function getThrottleDelay()
    {
        return $this->throttleDelay;
    }

    /**
     * @param int $throttleDelay
     */
    public function setThrottleDelay($throttleDelay)
    {
        $this->throttleDelay = $throttleDelay;
    }

    /**
     * @return int
     */
    public function getThrottleDelayFactor()
    {
        return $this->throttleDelayFactor;
    }

    /**
     * @param int $throttleDelayFactor
     */
    public function setThrottleDelayFactor($throttleDelayFactor)
    {
        $this->throttleDelayFactor = $throttleDelayFactor;
    }

    /**
     * @return string
     */
    public function getThrottleStrategy()
    {
        return $this->throttleStrategy;
    }

    /**
     * @param string $throttleStrategy
     */
    public function setThrottleStrategy($throttleStrategy)
    {
        $this->throttleStrategy = $throttleStrategy;
    }

    /**
     * Return the valid throttle strategies.
     *
     * @return array
     */
    public static function getAvailableThrottleStrategies()
    {
        return self::getAvailableRetryStrategies();
    }

    /**
     * @return bool
     */
    public function shouldThrowExceptions()
    {
        return $this->throwExceptions;
    }

    /**
     * @param bool $throwExceptions
     */
    public function setThrowExceptions($throwExceptions)
    {
        $this->throwExceptions = $throwExceptions;
    }

    /**
     * @param string $retryURI
     */
    public function setRetryURI($retryURI)
    {
        $this->retryURI = $retryURI;
    }

    /**
     * @param string $retryURI
     */
    public function setCallbackURI($callbackURI)
    {
        $this->callbackURI = $callbackURI;
    }

    /**
     * @param string $throttleURI
     */
    public function setThrottleURI($throttleURI)
    {
        $this->throttleURI = $throttleURI;
    }

    /**
     * @param string $failedURI
     */
    public function setFailedURI($failedURI)
    {
        $this->failedEndpoint = $this->getEndpointFactory()->createEndpoint($failedURI, EndpointFactory::MODE_PRODUCE);
    }

    /**
     * @param Exchange $ex
     */
    protected function prepareExchange(Exchange $ex)
    {
        if ($ex->hasOut()) {
            $ex->getOut()->setContext($ex->getIn()->getContext());
            $ex->setIn($ex->getOut());
            $ex->setOut(null);
        }
    }


    /**
     * Dispatch handler event depending on an event name
     *
     * @param Exchange $exchange
     * @param $eventName
     */
    protected function dispatchEvent(Exchange $exchange, $eventName)
    {
        $event = new HandlerEvent($eventName);
        $event->setTimestampToCurrent();
        $event->setExchange($exchange);
        $this->eventDispatcher->dispatch($eventName, $event);
    }

    /**
     * Dispatch an beforeHandle event
     *
     * @param Exchange $exchange
     */
    public function onHandleSuccess(Exchange $exchange)
    {
        $this->dispatchEvent($exchange, HandlerEvent::AFTER_HANDLE_EVENT_NAME);
    }

    /**
     * Dispatch an afterHandle event
     *
     * @param Exchange $exchange
     */
    public function onHandleStart(Exchange $exchange)
    {
        $this->dispatchEvent($exchange, HandlerEvent::BEFORE_HANDLE_EVENT_NAME);

    }

    /**
     * @param NewExchangeEvent $event
     *
     * @throws HandlerException
     */
    public function onNewExchangeEvent(NewExchangeEvent $event)
    {
        $newExchange = $event->getExchange();

        if ($newExchange->getHeader(Exchange::HEADER_HANDLER) == $this->getId()) {
            $event->stopPropagation();

            // If the exchange is to be deferred, we send it back to the original endpoint
            if ($this->shouldDeferNewExchanges()) {
                $newExchangeEnvelope = new Exchange(new DeferredExchangeEnvelope($newExchange));
                $uri = $newExchange->getHeader(Exchange::HEADER_FROM);
                $targetEndpoint = $this->getEndpointFactory()->createEndpoint($uri, EndpointFactory::MODE_PRODUCE);
                $targetEndpoint->produce($newExchangeEnvelope);
            }
            // Otherwise we process it immediately
            else {
                $this->processExchange($newExchange);
            }
        }
    }

    /**
     * If an exception occurs while trying to process or handle an exchange this method is called.
     *
     * We check the exception type to see what type of exception it was
     * - ThrottledException, here we will defer(deal with later) the message to the endpoint defined in the throttledURI
     * - RecoverableException, defer to the retryURI, only if we have not reached the max number of retires
     * - If the context has a callback we will put the failed message in a callback envelope and defer the exchange
     * - Else it is a failed exchange and it will be produced to the failure endpoint
     *
     * @param ProcessingException $exception
     * @param Processor           $processor
     * @param Exchange            $exchangeBackup
     * @param int                 $progress
     * @param int                 $retries
     *
     * @throws \Exception
     * @throws RecoverableExceptionInterface
     */
    public function onHandleException(
        ProcessingException $exception,
        Processor $processor,
        Exchange $exchangeBackup,
        $progress,
        $retries
    ) {
        $originalException = $exception->getOriginalException();
        $context = $exchangeBackup->getIn()->getContext();

        try {
            // Dispatch event with error information
            $event = new ProcessingErrorEvent($processor, $exchangeBackup, $originalException);
            $event->setId(uniqid('', true));
            $event->setTimestampToCurrent();
            $event->setProcessingContext($exception->getProcessingContext());
            $this->getEventDispatcher()->dispatch(ProcessingErrorEvent::EVENT_NAME, $event);

            // If it's just an exchange that should be retried later
            if ($originalException instanceof ThrottledException) {
                $throttledExchangeEnvelope = new ThrottledExchangeEnvelope($exchangeBackup, $exception->getProcessingContext(), $retries + 1);
                $this->addCommonErrorHeadersToEnvelope($throttledExchangeEnvelope, $exception, $processor, $retries);
                $this->deferExchangeMessage($throttledExchangeEnvelope, $this->throttleURI);
                $this->dispatchEvent($exchangeBackup, HandlerEvent::THROTTLING_HANDLE_EVENT_NAME);

            } // If it's an exchange that can be retried later but it's failing due to an error
            elseif ($originalException instanceof DelayException) {
                $delayPeriod = $exchangeBackup->getIn()->getHeader('delay');
                $delayExchangeEnvelope = new DelayedExchangeEnvelope($exchangeBackup, $delayPeriod);

                $fromQueue = $exchangeBackup->getHeader('from');
                $this->deferExchangeMessage($delayExchangeEnvelope, $fromQueue);
            }
            elseif ($originalException instanceof RecoverableExceptionInterface && $retries < $this->retriesMax) {

                $retryExchangeEnvelope = new RetryExchangeEnvelope($exchangeBackup, $exception->getProcessingContext(), $retries + 1);

                $this->addCommonErrorHeadersToEnvelope($retryExchangeEnvelope, $exception, $processor, $retries);
                $this->deferExchangeMessage($retryExchangeEnvelope, $this->retryURI);
                $this->dispatchEvent($exchangeBackup, HandlerEvent::RECOVERABLE_FAILED_EXCHANGE_EVENT_NAME);
            } elseif (null !== $this->callbackURI && true === $context->get(Context::CALLBACK) && $context->get(Context::CALLBACK_METHOD)) {
                $callbackExchangeEnvelope = new CallbackExchangeEnvelope($exchangeBackup, $exception->getProcessingContext());
                $this->addCallbackHeadersToEnvelope($callbackExchangeEnvelope, $exception, $processor);
                $this->deferExchangeMessage($callbackExchangeEnvelope, $this->callbackURI);
                $this->dispatchEvent($exchangeBackup, HandlerEvent::CALLBACK_HANDLE_EVENT_NAME);

            } else {
                $envelope = new FailedExchangeEnvelope($exchangeBackup, $exception->getProcessingContext());
                $this->addCommonErrorHeadersToEnvelope($envelope, $exception, $processor, $retries);
                $failedExchange = new Exchange($envelope);
                $this->failedEndpoint->produce($failedExchange);
                $this->dispatchEvent($failedExchange, HandlerEvent::UNRECOVERABLE_FAILED_EXCHANGE_EVENT_NAME);
            }
        } catch (\Exception $exceptionHandlingException) {
            $wrapException = new \Exception('Error while trying to handle Exception in the MessageHandler'.$exceptionHandlingException->getMessage(), 0, $exceptionHandlingException);
            $event = new HandlerErrorEvent($exchangeBackup, $wrapException);
            $event->setId(uniqid('', true));
            $event->setTimestampToCurrent();
            $this->getEventDispatcher()->dispatch(HandlerErrorEvent::EVENT_NAME, $event);
            throw $exceptionHandlingException;
        }

        if ($this->shouldThrowExceptions()) {
            throw $originalException;
        }
    }

    /**
     * This method adds headers to the Envelope that we put the Failed/Retry/Throttled exchange into, this is so that the
     * consumer of the has information to do deal with it.
     *
     * - add the number of retries
     * - add the delay for the message
     * - add the strategy used for calculating the delay
     * - add the last error message and the time the error happened
     * - add information about the processor that was being used when the event occurred
     *
     * @param ErrorExchangeEnvelope $envelope
     * @param ProcessingException   $exception
     * @param int                   $retries
     */
    private function addCommonErrorHeadersToEnvelope(ErrorExchangeEnvelope $envelope, ProcessingException $exception, ProcessorInterface $processor, $retries)
    {
        $originalException = $exception->getOriginalException();
        $errorDescription = $originalException ? $originalException->getMessage() : $exception->getMessage();

        if ($envelope instanceof RetryExchangeEnvelope) {
            $delay = $this->getRetryDelay();
            $delayProgressive = $delay * pow($this->getRetryDelayFactor(), $retries);
            $strategy = $this->getRetryStrategy();

            // override the values for the throttler
            if ($envelope instanceof ThrottledExchangeEnvelope) {
                $delay = $this->getThrottleDelay();
                $delayProgressive = $delay * pow($this->getThrottleDelayFactor(), $retries);
                $strategy = $this->getThrottleStrategy();
            }

            switch ($strategy) {
                case self::RETRY_STRATEGY_FIXED:
                    $envelope->setHeader(RetryExchangeEnvelope::HEADER_RETRY_DELAY, $delay);
                    break;
                case self::RETRY_STRATEGY_PROGRESSIVE:
                    $envelope->setHeader(RetryExchangeEnvelope::HEADER_RETRY_DELAY, $delayProgressive);
                    break;
                default:
                    throw new \RuntimeException("Unknown strategy $strategy.");
            }

            $envelope->setHeader(RetryExchangeEnvelope::HEADER_LAST_ERROR, $errorDescription);
            $envelope->setHeader(RetryExchangeEnvelope::HEADER_LAST_RETRY_AT, round(microtime(true) * 1000));
        }

        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_CREATED_AT, round(microtime(true) * 1000));
        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_ERROR_MESSAGE, $errorDescription);
        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_ERROR_PROCESSOR_ID, $processor->getId());
        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_ERROR_PROCESSOR_DESCRIPTION, $processor->getDescription());
        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_ERROR_NUM_RETRY, $retries);
    }

    /**
     * This method adds headers to the Envelope that we put the Callback exchange into, this is so that the
     * consumer of the has information to do deal with it.
     *
     * - add the last error message and the time the error happened
     * - add information about the processor that was being used when the event occurred
     *
     * @param CallbackExchangeEnvelope $envelope
     * @param ProcessingException      $exception
     * @param ProcessorInterface       $processor
     */
    private function addCallbackHeadersToEnvelope(CallbackExchangeEnvelope $envelope, ProcessingException $exception, ProcessorInterface $processor)
    {
        $originalException = $exception->getOriginalException();
        $errorDescription = $originalException ? $originalException->getMessage() : $exception->getMessage();

        $envelope->setHeader(CallbackExchangeEnvelope::HEADER_CREATED_AT, round(microtime(true) * 1000));
        $envelope->setHeader(CallbackExchangeEnvelope::HEADER_ERROR_MESSAGE, $errorDescription);
        $envelope->setHeader(CallbackExchangeEnvelope::HEADER_ERROR_PROCESSOR_ID, $processor->getId());
        $envelope->setHeader(CallbackExchangeEnvelope::HEADER_ERROR_PROCESSOR_DESCRIPTION, $processor->getDescription());
        $envelope->setHeader(CallbackExchangeEnvelope::HEADER_STATUS_CODE, $originalException->getCode());
    }

    /**
     * Handle a message.
     *
     * If the message is a retryable message and the message is not ready to be processed yet, we will re-defer the message.
     * Otherwise process the message by putting it as part of an exchange, and processing the exchange.
     *
     * {@inheritdoc}
     */
    public function handle(MessageInterface $message, EndpointInterface $endpointFrom)
    {
        $retries = 0;
        // If this is an exchange envelope, we extract the old exchange and prepare the new one
        if ($message && $message instanceof ExchangeEnvelope) {
            $oldExchange = $message->getBody();
            $exchange = new Exchange($oldExchange->getIn(), $oldExchange->getItinerary(), $oldExchange->getHeaders());

            if ($message instanceof RetryExchangeEnvelope) {
                $retries = $message->getRetries();
                $delaySinceLastRetry = round(microtime(true) * 1000) - $message->getHeader(RetryExchangeEnvelope::HEADER_LAST_RETRY_AT);
                $retryDelay = $message->getHeader(RetryExchangeEnvelope::HEADER_RETRY_DELAY) * 1000;

                $endpointURI = $message instanceof ThrottledExchangeEnvelope ? $this->throttleURI : $this->retryURI;

                if ($delaySinceLastRetry < $retryDelay) {
                    $this->deferExchangeMessage($message, $endpointURI);

                    return;
                }
            } elseif ($message instanceof DelayedExchangeEnvelope) {
                $headers = $message->getBody()->getIn()->getHeaders();
                unset($headers['delay']);
                $message->getBody()->getIn()->setHeaders($headers);
            }
        }
        // Otherwise create the exchange
        else {
            $exchange = $this->createExchangeForMessageFromURI($message, $endpointFrom->getURI());
        }

        $this->onHandleStart($exchange);
        $result = $this->processExchange($exchange, $retries);
        $this->onHandleSuccess($exchange);
        return $result;
    }

    /**
     * @param MessageInterface $message
     * @param string           $from
     *
     * @return Exchange
     *
     * @throws \Exception
     */
    protected function createExchangeForMessageFromURI(MessageInterface $message, $from)
    {
        $version = $message->getContext()->get(Context::FLOWS_VERSION);
        $params = $this->itineraryResolver->getItineraryParams($from, $version);
        $itinerary = $params[InternalRouter::KEY_ITINERARY];

        $exchange = new Exchange($message, clone $itinerary);
        $exchange->setHeader(Exchange::HEADER_HANDLER, $this->getId());
        $exchange->setHeader(Exchange::HEADER_FROM, $from);

        foreach ($this->itineraryResolver->filterItineraryParamsToPropagate($params) as $key => $value) {
            $exchange->setHeader($key, $value);
        }

        return $exchange;
    }

    /**
     * @param Exchange $exchange
     * @param int      $retries
     *
     * @return MessageInterface
     *
     * @throws \Exception
     * @throws HandlerException
     * @throws RecoverableExceptionInterface
     */
    public function processExchange(Exchange $exchange, $retries = 0)
    {
        $itinerary = $exchange->getItinerary();

        if (!$itinerary || empty($itinerary->getProcessorIds())) {
            throw new HandlerException('Itinerary not found while handling message', $exchange->getIn());
        }

        $progress = 0;
        $exchangeBackup = clone $exchange;

        while (null !== $processorId = $itinerary->shiftProcessorId()) {
            // Get the processor from the container
            if (!$this->container->has($processorId)) {
                throw new \RuntimeException("Processor with id $processorId not found.");
            }

            $processor = $this->container->get($processorId);
            if (!$processor instanceof ProcessorInterface) {
                throw new \RuntimeException("Processor with id $processorId does not implement ProcessorInterface.");
            }

            // Execute the processor
            try {
                $this->prepareExchange($exchange);

                $processor->process($exchange);

                ++$progress;
                $retries = 0;   // If we make any progress the retries count is restarted
                $exchangeBackup = clone $exchange;

                // Make sure that we use the updated itinerary
                $itinerary = $exchange->getItinerary();
            } catch (ProcessingException $exception) {
                $this->onHandleException($exception, $processor, $exchangeBackup, $progress, $retries);

                return;
            }
        }

        return $exchange->getResult();
    }

    /**
     * This is left for here for backwards compatibility
     * It is preferred to use deferExchangeMessage from now on with a $retryUri.
     *
     * @param ExchangeEnvelope $deferredExchange
     */
    public function deferRetryExchangeMessage(ExchangeEnvelope $deferredExchange)
    {
        $this->deferExchangeMessage($deferredExchange, $this->retryURI);
    }

    /**
     * Defer and ExchangeEnvelope to an endpoint
     * If no endpoint is defined then look inside the envelope for the exchange and use original endpoint.
     *
     * @param ExchangeEnvelope $deferredExchange
     * @param null             $endpointURI
     */
    public function deferExchangeMessage(ExchangeEnvelope $deferredExchange, $endpointURI = null)
    {
        $exchange = new Exchange($deferredExchange);

        if (!$endpointURI) {
            $oldExchange = $deferredExchange->getBody();
            $endpointURI = $oldExchange->getHeader(Exchange::HEADER_FROM);
        }

        $endpoint = $this->getEndpointFactory()->createEndpoint($endpointURI, EndpointFactory::MODE_PRODUCE);

        $endpoint->produce($exchange);
    }
}
