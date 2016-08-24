<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\SmokeTests;

use Smartbox\CoreBundle\Utils\SmokeTest\SmokeTestInterface;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\NoSQLDriverInterface;

/**
 * Class StorageConnectionSmokeTest.
 */
class StorageConnectionSmokeTest implements SmokeTestInterface
{
    /**
     * @var NoSQLDriverInterface
     */
    protected $storage;

    public function __construct(NoSQLDriverInterface $storage)
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
            $smokeTestOutput->addSuccessMessage('Connection checked.');
        } catch (\Exception $e) {
            $smokeTestOutput->setCode(SmokeTestOutput::OUTPUT_CODE_FAILURE);
            $smokeTestOutput->addFailureMessage($e->getMessage());
        }

        return $smokeTestOutput;
    }
}
