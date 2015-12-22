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
    protected $defaultQueueDriver;

    /**
     * @var ActiveMQStompQueueDriver
     */
    protected $eventsQueueDriver;

    public function __construct(ActiveMQStompQueueDriver $defaultQueueDriver, ActiveMQStompQueueDriver $eventsQueueDriver)
    {
        $this->defaultQueueDriver = $defaultQueueDriver;
        $this->eventsQueueDriver = $eventsQueueDriver;
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
            $this->defaultQueueDriver->connect();

            $smokeTestOutput->addMessage('Connection for default queue driver checked.');
        } catch (\Exception $e) {
            $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
            $smokeTestOutput->addMessage($e->getMessage());
        }

        try {
            $this->defaultQueueDriver->connect();

            $smokeTestOutput->addMessage('Connection for events queue driver checked.');
        } catch (\Exception $e) {
            $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
            $smokeTestOutput->addMessage($e->getMessage());
        }

        $smokeTestOutput->setCode($exitCode);

        return $smokeTestOutput;
    }
}