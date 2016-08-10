<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\SmokeTests;

use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutputInterface;

/**
 * Interface CanCheckConnectivityInterface
 */
interface CanCheckConnectivityInterface
{
    /**
     * @param array $config
     *
     * @return SmokeTestOutputInterface
     */
    public function checkConnectivityForSmokeTest(array $config = []);

    /**
     * @return string   a comma separated list of labels
     */
    public static function getConnectivitySmokeTestLabels();
}
