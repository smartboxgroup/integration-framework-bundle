<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\SmokeTests;

use Smartbox\CoreBundle\Utils\SmokeTest\SmokeTestInterface;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;

class FilePermissionsSmokeTest implements SmokeTestInterface
{
    private $cacheDir;

    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function getDescription()
    {
        return 'SmokeTest for file permissions.';
    }

    public function run()
    {
        $smokeTestOutput = new SmokeTestOutput();

        if (!is_writable($this->cacheDir)) {
            $smokeTestOutput->setCode(SmokeTestOutput::OUTPUT_CODE_FAILURE);
            $smokeTestOutput->addMessage('Cache directory (' . $this->cacheDir . ') should be writeable.');
        } else {
            $smokeTestOutput->setCode(SmokeTestOutput::OUTPUT_CODE_SUCCESS);
            $smokeTestOutput->addMessage('Cache directory permissions checked.');
        }

        return $smokeTestOutput;
    }
}