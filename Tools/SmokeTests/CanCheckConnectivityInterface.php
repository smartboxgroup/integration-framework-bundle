<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\SmokeTests;

use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutputInterface;

interface CanCheckConnectivityInterface
{
    /**
     * @param array|null $config
     * @return SmokeTestOutputInterface
     */
    public function checkConnectivityForSmokeTest(array $config = null);
}