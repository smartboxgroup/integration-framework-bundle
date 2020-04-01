<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest;

use GuzzleHttp\ClientInterface;

interface HttpClientInterface
{
    /**
     * @return void
     */
    public function setHttpClient(ClientInterface $client);

    public function getHttpClient(): ClientInterface;

    /**
     * Adds a new handler to the ClientInterface. Useful for tweaking error messages.
     *
     * @return mixed
     */
    public function addHandler(callable $middleware, string $name);
}
