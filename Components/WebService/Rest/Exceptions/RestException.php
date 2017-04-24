<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions;

use JMS\Serializer\Annotation as JMS;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\WebService\HasExternalSystemName;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Exception\ExternalSystemExceptionInterface;
use Smartbox\Integration\FrameworkBundle\Components\WebService\HasShowExternalSystemErrorMessage;

/**
 * Class RestException.
 */
class RestException extends \Exception implements SerializableInterface, ExternalSystemExceptionInterface
{
    use HasInternalType;
    use HasExternalSystemName;
    use HasShowExternalSystemErrorMessage;

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
    protected $responseStatusCode;

    /**
     * @param string $message
     * @param array $requestHeaders
     * @param string $requestBody
     * @param array $responseHeaders
     * @param string $responseBody
     * @param int $responseStatusCode
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct(
        $message,
        array $requestHeaders = [],
        $requestBody = '',
        $responseHeaders = [],
        $responseBody = '',
        $responseStatusCode = 0,
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->requestHttpHeaders = $requestHeaders;
        $this->requestHttpBody = $requestBody;
        $this->responseHttpHeaders = $responseHeaders;
        $this->responseHttpBody = $responseBody;
        $this->responseStatusCode = $responseStatusCode;
    }

    /**
     * @return boolean
     */
    public function isShowExternalSystemErrorMessage()
    {
        return $this->showExternalSystemErrorMessage;
    }

    /**
     * @param boolean $showExternalSystemErrorMessage
     */
    public function setShowExternalSystemErrorMessage($showExternalSystemErrorMessage)
    {
        $this->showExternalSystemErrorMessage = $showExternalSystemErrorMessage;
    }

    /**
     * @return array
     */
    public function getRequestHttpHeaders()
    {
        return $this->requestHttpHeaders;
    }

    /**
     * @param array $requestHttpHeaders
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
     * @return array
     */
    public function getResponseHttpHeaders()
    {
        return $this->responseHttpHeaders;
    }

    /**
     * @param array $responseHttpHeaders
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
    public function getResponseStatusCode()
    {
        return $this->responseStatusCode;
    }

    /**
     * @param string $responseStatusCode
     */
    public function setResponseStatusCode($responseStatusCode)
    {
        $this->responseStatusCode = $responseStatusCode;
    }

    /**
     * @param string $message
     */
    public function setMessage($message){
        $this->message = $message;
    }
}
