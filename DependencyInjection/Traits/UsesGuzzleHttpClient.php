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

    /**
     * @param ClientInterface $httpClient
     */
    public function setHttpClient(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

        if(null !== $handlerStack = $this->httpClient->getConfig('handler')) {
            $handlerStack->push(Middleware::httpErrors(), 'http_errors_handler');
        }

    }
}