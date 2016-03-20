<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\SmokeTests;

use Smartbox\CoreBundle\Utils\SmokeTest\SmokeTestInterface;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;
use Smartbox\Integration\FrameworkBundle\Components\DB\Storage\StorageClientInterface;

class StorageConnectionSmokeTest implements SmokeTestInterface
{
    /**
     * @var StorageClientInterface
     */
    protected $storage;

    public function __construct(StorageClientInterface $storage)
    {
        $this->storage = $storage;
    }

    public function getDescription()
    {
        return 'SmokeTest to check connection of storage driver.';
    }

    public function run()
    {
        $smokeTestOutput = new SmokeTestOutput();

        try {
            $this->storage->connect();

            $smokeTestOutput->setCode(SmokeTestOutput::OUTPUT_CODE_SUCCESS);
            $smokeTestOutput->addMessage('Connection checked.');
        } catch (\Exception $e) {
            $smokeTestOutput->setCode(SmokeTestOutput::OUTPUT_CODE_FAILURE);
            $smokeTestOutput->addMessage($e->getMessage());
        }

        return $smokeTestOutput;
    }
}