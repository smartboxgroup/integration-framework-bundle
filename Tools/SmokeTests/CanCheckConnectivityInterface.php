<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\SmokeTests;

use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutputInterface;

/**
 * Interface CanCheckConnectivityInterface.
 */
interface CanCheckConnectivityInterface
{
    /**
     * The label defines the critical level for smoke tests.
     * By default smoke-tests skips the WIP label
     */
    const SMOKE_TEST_LABEL_EMPTY = '';
    const SMOKE_TEST_LABEL_WIP = 'WIP';
    const SMOKE_TEST_LABEL_IMPORTANT = 'important';
    const SMOKE_TEST_LABEL_CRITICAL = 'critical';
    /**
     * @param array $config
     *
     * @return SmokeTestOutputInterface
     */
    public function checkConnectivityForSmokeTest(array $config = []);

    /**
     * @return string a comma separated list of labels
     */
    public static function getConnectivitySmokeTestLabels();
}
