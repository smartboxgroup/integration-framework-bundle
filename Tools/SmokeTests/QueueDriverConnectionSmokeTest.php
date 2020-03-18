<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\SmokeTests;

use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;
use Smartbox\CoreBundle\Utils\SmokeTest\SmokeTestInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;

/**
 * Class QueueDriverConnectionSmokeTest.
 */
class QueueDriverConnectionSmokeTest implements SmokeTestInterface
{
    /**
     * @var QueueDriverInterface
     */
    protected $queueDriver;

    public function __construct(QueueDriverInterface $queueDriver)
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

            if (!$this->queueDriver->isConnected()) {
                throw new \RuntimeException('Function isConnected() returned false.');
            }

            $message = new QueueMessage();
            $message->setTTL(1);
            $message->setQueue('isAlive');

            if (!$this->queueDriver->send($message)) {
                throw new \RuntimeException('Failed to insert message in queue.');
            };

            $smokeTestOutput->addSuccessMessage('Connection for default queue driver checked.');
        } catch (\Exception $e) {
            $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
            $smokeTestOutput->addFailureMessage($e->getMessage());
        }

        $smokeTestOutput->setCode($exitCode);

        return $smokeTestOutput;
    }
}
