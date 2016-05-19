<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Soap\Exceptions;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\CoreBundle\Exception\ExternalSystemExceptionInterface;
use Smartbox\CoreBundle\Type\Traits\HasExternalSystem;

/**
 * Class SoapException.
 */
class SoapException extends \Exception implements SerializableInterface, ExternalSystemExceptionInterface
{
    use HasInternalType;
    use HasExternalSystem;

    /**
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\SerializedName("requestHeaders")
     * @JMS\Groups({"logs"})
     */
    protected $requestHeaders;

    /**
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\SerializedName("request")
     * @JMS\Groups({"logs"})
     */
    protected $request;

    /**
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\SerializedName("responseHeaders")
     * @JMS\Groups({"logs"})
     */
    protected $responseHeaders;

    /**
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\SerializedName("response")
     * @JMS\Groups({"logs"})
     */
    protected $response;

    /**
     * SoapException constructor.
     *
     * @param string          $message
     * @param string          $requestHeaders
     * @param string          $request
     * @param string          $responseHeaders
     * @param string          $response
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct(
        $message,
        $requestHeaders = null,
        $request = null,
        $responseHeaders = null,
        $response = null,
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->requestHeaders = $requestHeaders;
        $this->request = $request;
        $this->responseHeaders = $responseHeaders;
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getRequestHeaders()
    {
        return $this->requestHeaders;
    }

    /**
     * @param string $requestHeaders
     *
     * @return SoapException
     */
    public function setRequestHeaders($requestHeaders)
    {
        $this->requestHeaders = $requestHeaders;

        return $this;
    }

    /**
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param string $request
     *
     * @return SoapException
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return string
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * @param string $responseHeaders
     *
     * @return SoapException
     */
    public function setResponseHeaders($responseHeaders)
    {
        $this->responseHeaders = $responseHeaders;

        return $this;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $response
     *
     * @return SoapException
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }
}
