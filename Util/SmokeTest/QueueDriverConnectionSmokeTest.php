<?php

namespace Smartbox\Integration\FrameworkBundle\Util\SmokeTest;

use Smartbox\CoreBundle\Utils\SmokeTest\SmokeTestInterface;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;
use Smartbox\Integration\FrameworkBundle\Drivers\Queue\ActiveMQStompQueueDriver;

class QueueDriverConnectionSmokeTest implements SmokeTestInterface
{
    /**
     * @var ActiveMQStompQueueDriver
     */
    protected $queueDriver;

    public function __construct(ActiveMQStompQueueDriver $queueDriver)
    {
        $this->queueDriver = $queueDriver;
    }

    public function getDescription()
    {
        return 'SmokeTest to check connection of activeMQ driver.';
    }

    public function run()
    {
        $smokeTestOutput = new SmokeTestOutput();
        $exitCode = SmokeTestOutput::OUTPUT_CODE_SUCCESS;

        try {
            $this->queueDriver->connect();

            $smokeTestOutput->addMessage('Connection for default queue driver checked.');
        } catch (\Exception $e) {
            $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
            $smokeTestOutput->addMessage($e->getMessage());
        }

        $smokeTestOutput->setCode($exitCode);

        return $smokeTestOutput;
    }
}