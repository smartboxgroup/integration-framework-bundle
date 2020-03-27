<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest;

use GuzzleHttp\ClientInterface;

interface HttpClientInterface
{
    public function setHttpClient(ClientInterface $client);

    public function addHandler();

    public function getHttpClient(): ClientInterface;
}
