<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;

class HttpClient extends Client
{
    public function __construct(array $config = [])
    {
        $this->addHandler($config);
        parent::__construct($config);
    }

    protected function addHandler(array &$config = [])
    {
        $handler = $config['handler'];

        if(isset($handler) && class_exists($handler) && (new $handler) instanceof HttpHandlerInterface) {
            $config['handler'] = $handler::create($config);
        }
    }
}
