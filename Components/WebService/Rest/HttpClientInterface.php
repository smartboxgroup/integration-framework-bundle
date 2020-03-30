<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest;

use GuzzleHttp\ClientInterface;

interface HttpClientInterface
{
    /**
     * @param ClientInterface $client
     * 
     * @return void
     */
    public function setHttpClient(ClientInterface $client);

    /**
     * @return ClientInterface
     */
    public function getHttpClient(): ClientInterface;

    /**
     * Adds a new handler to the ClientInterface. Useful for tweaking error messages.
     * 
     * @return void
     */
    public function addHandler();
}
