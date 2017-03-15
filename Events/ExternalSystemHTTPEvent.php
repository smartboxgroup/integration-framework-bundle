<?php

namespace Smartbox\Integration\FrameworkBundle\Events;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ExternalSystemHTTPEvent.
 */
class ExternalSystemHTTPEvent extends Event
{
    const EVENT_NAME = 'smartesb.event.external_system_http_event';

    /**
     *
     */
    public function __construct()
    {
        error_log("MEL_HTTP_EVENT:CONSTRUCTOR.\n");
        parent::__construct(self::EVENT_NAME);
    }

    /**
     * @var string ******
     */
    protected $context;

    /**
     * @var string ******
     */
    protected $exchangeId;

    /**
     * @var string ******
     */
    protected $endpointUri;

    /**
     * @var string ******
     */
    protected $requestHttpHeaders;

    /**
     * @var string ******
     */
    protected $requestHttpBody;

    /**
     * @var string ******
     */

    protected $responseHttpHeaders;

    /**
     * @var string ******
     */
    protected $responseHttpBody;

    /**
     * @var string ******
     */
    protected $status;

    /**
     * @var string ******
     */
    protected $exception;

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param string $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getExchangeId()
    {
        return $this->exchangeId;
    }

    /**
     * @param string $exchangeId
     */
    public function setExchangeId($exchangeId)
    {
        $this->exchangeId = $exchangeId;
    }

    /**
     * @return string
     */
    public function getEndpointUri()
    {
        return $this->endpointUri;
    }

    /**
     * @param string $endpointUri
     */
    public function setEndpointUri($endpointUri)
    {
        $this->endpointUri = $endpointUri;
    }

    /**
     * @return string
     */
    public function getRequestHttpHeaders()
    {
        return $this->requestHttpHeaders;
    }

    /**
     * @param string $requestHttpHeaders
     */
    public function setRequestHttpHeaders($requestHttpHeaders)
    {
        $this->requestHttpHeaders = $requestHttpHeaders;
    }

    /**
     * @return string
     */
    public function getRequestHttpBody()
    {
        return $this->requestHttpBody;
    }

    /**
     * @param string $requestHttpBody
     */
    public function setRequestHttpBody($requestHttpBody)
    {
        $this->requestHttpBody = $requestHttpBody;
    }

    /**
     * @return string
     */
    public function getResponseHttpHeaders()
    {
        return $this->responseHttpHeaders;
    }

    /**
     * @param string $responseHttpHeaders
     */
    public function setResponseHttpHeaders($responseHttpHeaders)
    {
        $this->responseHttpHeaders = $responseHttpHeaders;
    }

    /**
     * @return string
     */
    public function getResponseHttpBody()
    {
        return $this->responseHttpBody;
    }

    /**
     * @param string $responseHttpBody
     */
    public function setResponseHttpBody($responseHttpBody)
    {
        $this->responseHttpBody = $responseHttpBody;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param string $exception
     */
    public function setException($exception)
    {
        $this->exception = $exception;
    }



}
