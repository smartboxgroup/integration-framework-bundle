<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;

/**
 * Class RestException
 *
 * @package \Smartbox\Integration\FrameworkBundle\Exceptions
 */
class RestException extends \Exception implements SerializableInterface
{
    use HasInternalType;

    /**
     * @var \Psr\Http\Message\RequestInterface
     * @JMS\Expose
     * @JMS\Type("GuzzleHttp\Psr7\Request")
     * @JMS\Groups({"logs"})
     */
    protected $request;

    /**
     * @var \Psr\Http\Message\ResponseInterface
     * @JMS\Expose
     * @JMS\Type("GuzzleHttp\Psr7\Response")
     * @JMS\Groups({"logs"})
     */
    protected $response;

    /**
     * RestException constructor.
     *
     * @param string                              $message
     * @param \Psr\Http\Message\RequestInterface  $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param                                     $code
     * @param \Exception                          $previous
     */
    public function __construct(
        $message,
        RequestInterface $request = null,
        ResponseInterface $response = null,
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
