<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\HttpClientRequestException;

/**
 * Guzzle Middlewae to be used by HttpClient.
 */
class Middleware
{
    public static $truncateResponseSize = 120;

    /**
     * This method override the default one used by Guzzle.
     * Using this we can handle the RequestException the way we want by also overriding it with HttpClientRequestException.
     *
     * If you need to manage the size of response, set it as int at truncate_response_size config parameter
     *
     * @return \Closure
     */
    public static function httpErrors()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($request, $handler, $options) {
                        $code = $response->getStatusCode();
                        if ($code < 400) {
                            return $response;
                        }

                        if (isset($options['truncate_response_size'])) {
                            self::$truncateResponseSize = $options['truncate_response_size'];
                        }

                        throw HttpClientRequestException::create($request, $response);
                    }
                );
            };
        };
    }

    public static function handleResponseBody(ResponseInterface $response)
    {
        $body = $response->getBody();

        if (!$body->isSeekable()) {
            return null;
        }

        $size = $body->getSize();

        if (0 === $size) {
            return null;
        }

        $summary = $body->read($size);

        if (self::$truncateResponseSize > 0 && $size > self::$truncateResponseSize) {
            $summary = mb_substr($summary, 0, self::$truncateResponseSize).' (truncated...)';
        }

        if (preg_match('/[^\pL\pM\pN\pP\pS\pZ\n\r\t]/', $summary)) {
            return null;
        }

        return $summary;
    }
}
