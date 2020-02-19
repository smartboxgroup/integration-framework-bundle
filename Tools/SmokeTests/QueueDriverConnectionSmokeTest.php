<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\SmokeTests;

use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;
use Smartbox\CoreBundle\Utils\SmokeTest\SmokeTestInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\AsyncQueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;

/**
 * Class QueueDriverConnectionSmokeTest.
 */
class QueueDriverConnectionSmokeTest implements SmokeTestInterface
{
    /**
     * @var QueueDriverInterface
     */
    protected $queueDriver;

    public function __construct(AsyncQueueDriverInterface $queueDriver)
    {
        $this->queueDriver = $queueDriver;
    }

    public function getDescription()
    {
        return 'SmokeTest to check connection of queue driver.';
    }

    public function run()
    {
        $smokeTestOutput = new SmokeTestOutput();
        $exitCode = SmokeTestOutput::OUTPUT_CODE_SUCCESS;

        try {
            $this->queueDriver->connect();

            //we need to subscribe to a queue to connect
            $this->queueDriver->subscribe('prod');
            $this->queueDriver->unSubscribe();  // tidy up

            $smokeTestOutput->addSuccessMessage('Connection for default queue driver checked.');
        } catch (\Exception $e) {
            $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
            $smokeTestOutput->addFailureMessage($e->getMessage());
        }

        $smokeTestOutput->setCode($exitCode);

        return $smokeTestOutput;
    }
}
