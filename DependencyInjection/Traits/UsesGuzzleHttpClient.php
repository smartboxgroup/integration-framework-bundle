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
    }
}
