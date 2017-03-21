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
     * ExternalSystemHTTPEvent Constructor
     */
    public function __construct()
    {
        parent::__construct(self::EVENT_NAME);
    }

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $transactionId;

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $exchangeId;

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $httpURI;

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $fromUri;

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("array")
     *
     * @var array
     */
    protected $requestHttpHeaders;

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $requestHttpBody;

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("array")
     *
     * @var array
     */
    protected $responseHttpHeaders;

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $responseHttpBody;

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $status;

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return string
     */
    public function getHttpURI()
    {
        return $this->httpURI;
    }

    /**
     * @param string $httpURI
     */
    public function setHttpURI($httpURI)
    {
        $this->httpURI = $httpURI;
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
    public function getFromUri()
    {
        return $this->fromUri;
    }

    /**
     * @param string $fromUri
     */
    public function setFromUri($fromUri)
    {
        $this->fromUri = $fromUri;
    }



}
