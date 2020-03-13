<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;

class HttpClient extends Client
{
    public function __construct(array $config = [])
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $stack->push((new Middleware())->httpErrors(), 'http_errors_handler');
        
        $config['http_errors'] = false;
        $config['handler'] = $stack;

        parent::__construct($config);
    }
}
