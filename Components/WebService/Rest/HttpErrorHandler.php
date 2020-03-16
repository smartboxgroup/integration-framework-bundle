<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;

/**
 * Create a proper error handler to be used by Guzzle
 * Please make sure to set http_errors as false on guzzle config
 * Also, if you need to manage the size of response, set it as int at truncate_response_size config parameter
 */
class HttpErrorHandler implements HttpHandlerInterface
{
    public static function create(array $config = []): HandlerStack
    {
        if(!isset($config['http_errors']) || true === $config['http_errors']) {
            throw new \InvalidArgumentException('You must set http_errors to false to use ' . self::class);
        }

        $handler = new CurlHandler();
        $stack = HandlerStack::create($handler);
        $stack->push(Middleware::httpErrors(), 'http_errors_handler');

        return $stack;
    }
}
