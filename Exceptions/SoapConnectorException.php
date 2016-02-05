<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions;


class SoapConnectorException extends ConnectorRecoverableException {

    /**
     * @JMS\Type("string")
     * @JMS\Expose
     * @var string
     */
    protected $rawRequest;

    /**
     * @JMS\Type("string")
     * @JMS\Expose
     * @var string
     */
    protected $rawResponse;

    /**
     * @return string
     */
    public function getRawRequest()
    {
        return $this->rawRequest;
    }

    /**
     * @param string $rawRequest
     */
    public function setRawRequest($rawRequest)
    {
        $this->rawRequest = $rawRequest;
    }

    /**
     * @return string
     */
    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    /**
     * @param string $rawResponse
     */
    public function setRawResponse($rawResponse)
    {
        $this->rawResponse = $rawResponse;
    }
}