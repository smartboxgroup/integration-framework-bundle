<?php

namespace Smartbox\Integration\FrameworkBundle\Traits;


use GuzzleHttp\ClientInterface;

trait UsesGuzzleHttpClient {

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