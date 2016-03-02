<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;

class SoapConnectorException extends \Exception implements SerializableInterface {

    use HasInternalType;

    /**
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @var string
     */
    protected $rawRequest;

    /**
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
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