<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\HttpClientRequestException;

/**
 * Guzzle Middlewae to be used by HttpClient
 */
class Middleware
{
    /**
     * This method override the default one used by Guzzle.
     * Using this we can handle the RequestException the way we want by also overriding it with HttpClientRequestException
     * 
     * @return \Closure
     * 
     */
    public function httpErrors()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($request, $handler, $options) {
                        $code = $response->getStatusCode();
                        if ($code < 400) {
                            return $response;
                        }
                        $truncateResponseSize = $options['truncate_response_size'] ?: 0;
                        HttpClientRequestException::setTruncateResponseSize($truncateResponseSize);
                        throw HttpClientRequestException::create($request, $response);
                    }
                );
            };
        };
    }
}
