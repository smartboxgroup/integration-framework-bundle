<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Middleware;

class HttpClientRequestException extends RequestException
{
    /**
     * Override the current getResponseBodySummary from RequestException
     * to avoid the truncation done default by guzzle at 120 chars
     *
     * @param ResponseInterface $response
     * @return false|string|null
     */
    public static function getResponseBodySummary(ResponseInterface $response)
    {
        return Middleware::handleResponseBody($response);
    }
}
