<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Handlers;

use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Core\Messages\DeferredExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Messages\ErrorExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Messages\ExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Messages\FailedExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\RetryExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\RetryLaterException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Core\Processors\ProcessorInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesItineraryResolver;
use Smartbox\Integration\FrameworkBundle\Events\HandlerEvent;
use Smartbox\Integration\FrameworkBundle\Events\NewExchangeEvent;
use Smartbox\Integration\FrameworkBundle\Events\ProcessingErrorEvent;
use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface;
use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEndpointFactory;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEventDispatcher;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class MessageHandler.
 */
class MessageHandler extends Service implements HandlerInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use UsesEventDispatcher;
    use UsesEndpointFactory;
    use UsesItineraryResolver;

    /** @var int */
    protected $retriesMax;

    /** @var int */
    protected $retryDelay;

    /** @var bool */
    protected $throwExceptions;

    /** @var bool */
    protected $deferNewExchanges;

    /** @var EndpointInterface */
    protected $failedEndpoint;

    /** @var EndpointInterface */
    protected $retryEndpoint;

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
        if (empty($retryURI)) {
            $this->retryEndpoint = null;
        } else {
            $this->retryEndpoint = $this->getEndpointFactory()->createEndpoint($retryURI);
        }
    }

    /**
     * @param string $failedURI
     */
    public function setFailedURI($failedURI)
    {
        $this->failedEndpoint = $this->getEndpointFactory()->createEndpoint($failedURI);
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
     * @param Exchange $exchange
     */
    public function onHandleStart(Exchange $exchange)
    {
        $beforeHandleEvent = new HandlerEvent(HandlerEvent::BEFORE_HANDLE_EVENT_NAME);
        $beforeHandleEvent->setTimestampToCurrent();
        $beforeHandleEvent->setExchange($exchange);
        $this->eventDispatcher->dispatch(HandlerEvent::BEFORE_HANDLE_EVENT_NAME, $beforeHandleEvent);
    }

    /**
     * @param Exchange $exchange
     */
    public function onHandleSuccess(Exchange $exchange)
    {
        $afterHandleEvent = new HandlerEvent(HandlerEvent::AFTER_HANDLE_EVENT_NAME);
        $afterHandleEvent->setTimestampToCurrent();
        $afterHandleEvent->setExchange($exchange);
        $this->eventDispatcher->dispatch(HandlerEvent::AFTER_HANDLE_EVENT_NAME, $afterHandleEvent);
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
                $targetEndpoint = $this->getEndpointFactory()->createEndpoint($uri);
                $targetEndpoint->produce($newExchangeEnvelope);
            }
            // Otherwise we process it immediately
            else {
                $this->processExchange($newExchange);
            }
        }
    }

    /**
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

        // Dispatch event with error information
        $event = new ProcessingErrorEvent($processor, $exchangeBackup, $originalException);
        $event->setId(uniqid('', true));
        $event->setTimestampToCurrent();
        $event->setProcessingContext($exception->getProcessingContext());

        // If it's just an exchange that should be retried later
        if ($originalException instanceof RetryLaterException) {
            $retryExchangeEnvelope = new RetryExchangeEnvelope($exchangeBackup, $exception->getProcessingContext(), 0);

            $this->addCommonErrorHeadersToEnvelope($retryExchangeEnvelope, $exception, $processor, 0);
            $retryExchangeEnvelope->setHeader(RetryExchangeEnvelope::HEADER_RETRY_DELAY, $originalException->getDelay());
            $this->deferRetryExchangeMessage($retryExchangeEnvelope);
        }

        // If it's an exchange that can be retried later but it's failing due to an error
        elseif ($originalException instanceof RecoverableExceptionInterface && $retries < $this->retriesMax) {
            $retryExchangeEnvelope = new RetryExchangeEnvelope($exchangeBackup, $exception->getProcessingContext(), $retries + 1);

            $this->addCommonErrorHeadersToEnvelope($retryExchangeEnvelope, $exception, $processor, $retries);
            $this->deferRetryExchangeMessage($retryExchangeEnvelope);
        }

        // If it's an exchange that is failing and it should not be retried later
        else {
            $envelope = new FailedExchangeEnvelope($exchangeBackup, $exception->getProcessingContext());
            $this->addCommonErrorHeadersToEnvelope($envelope, $exception, $processor, $retries);

            $failedExchange = new Exchange($envelope);
            $this->failedEndpoint->produce($failedExchange);
        }

        $this->getEventDispatcher()->dispatch(ProcessingErrorEvent::EVENT_NAME, $event);

        if ($this->shouldThrowExceptions()) {
            throw $originalException;
        }
    }

    /**
     * @param ErrorExchangeEnvelope $envelope
     * @param ProcessingException   $exception
     * @param int                   $retries
     */
    private function addCommonErrorHeadersToEnvelope(ErrorExchangeEnvelope $envelope, ProcessingException $exception, ProcessorInterface $processor, $retries)
    {
        $originalException = $exception->getOriginalException();
        $errorDescription = $originalException ? $originalException->getMessage() : $exception->getMessage();

        if ($envelope instanceof RetryExchangeEnvelope) {
            $envelope->setHeader(RetryExchangeEnvelope::HEADER_LAST_ERROR, $errorDescription);
            $envelope->setHeader(RetryExchangeEnvelope::HEADER_LAST_RETRY_AT, round(microtime(true) * 1000));
            $envelope->setHeader(RetryExchangeEnvelope::HEADER_RETRY_DELAY, $this->getRetryDelay());
        }

        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_CREATED_AT, round(microtime(true) * 1000));
        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_ERROR_MESSAGE, $errorDescription);
        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_ERROR_PROCESSOR_ID, $processor->getId());
        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_ERROR_PROCESSOR_DESCRIPTION, $processor->getDescription());
        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_ERROR_NUM_RETRY, $retries);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MessageInterface $message, EndpointInterface $endpointFrom)
    {
        $retries = 0;

        // If this is an exchange envelope
        if ($message && $message instanceof ExchangeEnvelope) {
            $exchange = $message->getBody();

            if ($message instanceof RetryExchangeEnvelope) {
                $retries = $message->getRetries();
                $delaySinceLastRetry = round(microtime(true) * 1000) - $message->getHeader(RetryExchangeEnvelope::HEADER_LAST_RETRY_AT);
                $retryDelay = $message->getHeader(RetryExchangeEnvelope::HEADER_RETRY_DELAY) * 1000;
                if ($delaySinceLastRetry < $retryDelay) {
                    $this->deferRetryExchangeMessage($message);

                    return;
                }
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
     * @param ExchangeEnvelope $deferredExchange
     */
    public function deferRetryExchangeMessage(ExchangeEnvelope $deferredExchange)
    {
        $oldExchange = $deferredExchange->getBody();

        $exchange = new Exchange($deferredExchange);

        $retryEndpoint = $this->retryEndpoint;
        if (!$retryEndpoint) {
            $retryURI = $oldExchange->getHeader(Exchange::HEADER_FROM);
            $retryEndpoint = $this->getEndpointFactory()->createEndpoint($retryURI);
        }

        $retryEndpoint->produce($exchange);
    }
}
