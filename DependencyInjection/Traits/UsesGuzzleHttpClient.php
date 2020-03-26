<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use GuzzleHttp\ClientInterface;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Middleware;

/**
 * Trait UsesGuzzleHttpClient.
 */
trait UsesGuzzleHttpClient
{
    /** @var ClientInterface */
    protected $httpClient;

    /**
     * @return ClientInterface
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    public function setHttpClient(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Push a new Error Handler to the current client.
     */
    public function addHandler()
    {
        if (null !== $this->httpClient && null !== $handlerStack = $this->httpClient->getConfig('handler')) {
            $handlerStack->push(Middleware::httpErrors(), 'http_errors_handler');
        }
    }
}
