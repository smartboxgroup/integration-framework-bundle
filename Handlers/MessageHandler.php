<?php

namespace Smartbox\Integration\FrameworkBundle\Handlers;

use Smartbox\Integration\FrameworkBundle\Events\Error\ProcessingErrorEvent;
use Smartbox\Integration\FrameworkBundle\Events\HandlerEvent;
use Smartbox\Integration\FrameworkBundle\Events\NewExchangeEvent;
use Smartbox\Integration\FrameworkBundle\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Messages\DeferredExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Messages\ErrorExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\ExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Messages\FailedExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Messages\RetryExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Processors\ProcessorInterface;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\Traits\UsesConnectorsRouter;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEventDispatcher;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Traits\UsesItinerariesRouter;
use Smartbox\Integration\FrameworkBundle\Exceptions\HandlerException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Class MessageHandler
 * @package Smartbox\Integration\FrameworkBundle\Handlers
 */
class MessageHandler extends Service implements HandlerInterface
{
    use UsesEventDispatcher;
    use UsesConnectorsRouter;
    use UsesItinerariesRouter;

    protected $retriesMax;

    /** @var string */
    protected $failedURI;

    /** @var string */
    protected $retryURI;

    /** @var  boolean */
    protected $throwExceptions;

    /** @var  boolean */
    protected $deferNewExchanges;

    /**
     * @return boolean
     */
    public function shouldDeferNewExchanges()
    {
        return $this->deferNewExchanges;
    }

    /**
     * @param boolean $deferNewExchanges
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
     * @return boolean
     */
    public function shouldThrowExceptions()
    {
        return $this->throwExceptions;
    }

    /**
     * @param boolean $throwExceptions
     */
    public function setThrowExceptions($throwExceptions)
    {
        $this->throwExceptions = $throwExceptions;
    }

    /**
     * @return string
     */
    public function getRetryURI()
    {
        return $this->retryURI;
    }

    /**
     * @param string $retryURI
     */
    public function setRetryURI($retryURI = null)
    {
        $this->retryURI = $retryURI;
    }

    /**
     * @return string
     */
    public function getFailedURI()
    {
        return $this->failedURI;
    }

    /**
     * @param string $failedURI
     */
    public function setFailedURI($failedURI)
    {
        $this->failedURI = $failedURI;

    }

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
    public function onHandleStart(Exchange $exchange){
        $beforeHandleEvent = new HandlerEvent(HandlerEvent::BEFORE_HANDLE_EVENT_NAME);
        $beforeHandleEvent->setExchange($exchange);
        $this->eventDispatcher->dispatch(HandlerEvent::BEFORE_HANDLE_EVENT_NAME, $beforeHandleEvent);
    }

    /**
     * @param Exchange $exchange
     */
    public function onHandleSuccess(Exchange $exchange){
        $afterHandleEvent = new HandlerEvent(HandlerEvent::AFTER_HANDLE_EVENT_NAME);
        $afterHandleEvent->setExchange($exchange);
        $this->eventDispatcher->dispatch(HandlerEvent::BEFORE_HANDLE_EVENT_NAME, $afterHandleEvent);
    }

    public function onNewExchangeEvent(NewExchangeEvent $event){
        $newExchange = $event->getExchange();

        if($newExchange->getHeader(Exchange::HEADER_HANDLER) == $this->getId()){
            $event->stopPropagation();

            // If the exchange is to be deferred, we send it back to the original endpoint
            if($this->shouldDeferNewExchanges()){
                $newExchangeEnvelope = new Exchange(new DeferredExchangeEnvelope($newExchange));
                $this->sendTo($newExchangeEnvelope,$newExchange->getHeader(Exchange::HEADER_FROM));
            }
            // Otherwise we process it immediately
            else{
                $this->processExchange($newExchange);
            }
        }
    }

    /**
     * @param ProcessingException $exception
     * @param Processor $processor
     * @param Exchange $exchangeBackup
     * @param $progress
     * @param $retries
     * @throws HandlerException
     * @throws \Exception
     */
    public function onHandleException(ProcessingException $exception, Processor $processor, Exchange $exchangeBackup, $progress, $retries){

        $originalException = $exception->getOriginalException();

        // Dispatch event with error information
        $event = new ProcessingErrorEvent($processor, $exchangeBackup, $originalException);
        $event->setProcessingContext($exception->getProcessingContext());

        if($this->shouldThrowExceptions()){
            $event->mustThrowException();
        }else{
            $event->mustNotThrowException();
        }

        // Try to recover
        if ($originalException instanceof RecoverableExceptionInterface && $retries < $this->retriesMax) {
            $retryExchangeEnvelope = new RetryExchangeEnvelope($exchangeBackup, $exception->getProcessingContext(), $retries+1);

            $this->addCommonErrorHeadersToEnvelope($retryExchangeEnvelope, $exception, $processor, $retries);
            $recoveryExchange = new Exchange($retryExchangeEnvelope);

            $retryUri = $this->retryURI;
            if (!$retryUri) {
                $retryUri = $exchangeBackup->getHeader(Exchange::HEADER_FROM);
            }

            $this->sendTo($recoveryExchange,$retryUri);
        }
        // Or not..
        else {
            $envelope = new FailedExchangeEnvelope($exchangeBackup, $exception->getProcessingContext());
            $this->addCommonErrorHeadersToEnvelope($envelope, $exception, $processor, $retries);

            $failedExchange = new Exchange($envelope);
            $this->sendTo($failedExchange,$this->failedURI);
        }

        $this->getEventDispatcher()->dispatch(ProcessingErrorEvent::EVENT_NAME, $event);
    }

    /**
     * @param ErrorExchangeEnvelope $envelope
     * @param ProcessingException $exception
     * @param int $retries
     */
    private function addCommonErrorHeadersToEnvelope(ErrorExchangeEnvelope $envelope, ProcessingException $exception, ProcessorInterface $processor, $retries)
    {
        $originalException = $exception->getOriginalException();
        $errorDescription = $originalException ? $originalException->getMessage() : $exception->getMessage();

        if ($envelope instanceof RetryExchangeEnvelope) {
            $envelope->setHeader(RetryExchangeEnvelope::HEADER_LAST_ERROR, $errorDescription);
        }

        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_CREATED_AT, round(microtime(true) * 1000));
        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_ERROR_MESSAGE, $errorDescription);
        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_ERROR_PROCESSOR_ID, $processor->getId());
        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_ERROR_PROCESSOR_DESCRIPTION, $processor->getDescription());
        $envelope->setHeader(ErrorExchangeEnvelope::HEADER_ERROR_NUM_RETRY, $retries);
    }

    /**
     * {@inheritDoc}
     */
    public function handle(MessageInterface $message, $from = null)
    {
        $version = $message->getContext()->get(Context::VERSION);
        $expectedVersion = $this->getFlowsVersion();

        if($version !== $expectedVersion){
            throw new HandlerException(
                "Received message with wrong version. Expected: $expectedVersion, received: $version"
                ,$message
            );
        }

        $retries = 0;

        // If this is an exchange envelope
        if($message && $message instanceof ExchangeEnvelope){
            $exchange = $message->getBody();

            if($message instanceof RetryExchangeEnvelope){
                $retries = $message->getRetries();
            }
        }
        // Otherwise create the exchange
        else{
            // Find from URI
            if(!$from){
                $from = $message->getHeader(Message::HEADER_FROM);
            }
            if(empty($from)){
                throw new HandlerException("Missing FROM header while trying to handle a message",$message);
            }

            $exchange = $this->createExchangeForMessageFromURI($message,$from);
        }

        $this->onHandleStart($exchange);
        $result = $this->processExchange($exchange,$retries);
        $this->onHandleSuccess($exchange);

        return $result;
    }

    protected function createExchangeForMessageFromURI(MessageInterface $message, $from){
        $params = $this->findItineraryParams($from);
        $itinerary = $params[InternalRouter::KEY_ITINERARY];

        $exchange = new Exchange($message, clone $itinerary);
        $exchange->setHeader(Exchange::HEADER_HANDLER,$this->getId());
        $exchange->setHeader(Exchange::HEADER_FROM,$from);

        foreach ($this->filterItineraryParamsToPropagate($params) as $key => $value) {
            $exchange->setHeader($key,$value);
        }

        return $exchange;
    }

    public function processExchange(Exchange $exchange, $retries = 0){
        $itinerary = $exchange->getItinerary();

        if(!$itinerary || empty($itinerary->getProcessors())){
            throw new HandlerException("Itinerary not found while handling message",$exchange->getIn());
        }

        $progress = 0;
        $exchangeBackup = clone $exchange;

        while (null !== $processor = $itinerary->shiftProcessor()) {
            try {
                $this->prepareExchange($exchange);

                $processor->process($exchange);

                $progress++;
                $retries = 0;   // If we make any progress the retries count is restarted
                $exchangeBackup = clone $exchange;
            }catch (ProcessingException $exception) {
                $this->onHandleException($exception, $processor, $exchangeBackup, $progress, $retries);
                return null;
            }
        }

        return $exchange->getResult();
    }
}
