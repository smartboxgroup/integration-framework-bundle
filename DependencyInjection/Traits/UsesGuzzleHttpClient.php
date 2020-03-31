<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use GuzzleHttp\ClientInterface;

/**
 * Trait UsesGuzzleHttpClient.
 */
trait UsesGuzzleHttpClient
{
    /** @var ClientInterface */
    protected $httpClient;

    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    public function setHttpClient(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function addHandler(callable $middleware, string $name)
    {
        if (null !== $this->httpClient && null !== $handlerStack = $this->httpClient->getConfig('handler')) {
            $handlerStack->push($middleware, $name);
        }
    }
}
