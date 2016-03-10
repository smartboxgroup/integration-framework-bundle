<?php

namespace Smartbox\Integration\FrameworkBundle\Util\SmokeTest;

use Smartbox\CoreBundle\Utils\SmokeTest\SmokeTestInterface;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;

class ConnectivityCheckSmokeTest implements SmokeTestInterface
{
    const TAG_TEST_CONNECTIVITY = 'smartesb.smoke_test.test_connectivity';

    /**
     * @var CanCheckConnectivityInterface[]
     */
    protected $items = [];

    /**
     * @param $name
     * @param CanCheckConnectivityInterface $item
     */
    public function addItem($name, CanCheckConnectivityInterface $item)
    {
        if (array_key_exists($name, $this->items)) {
            throw new \RuntimeException(
                sprintf(
                    'Item with name "%s" already exists. Please provide unique name.',
                    $name
                )
            );
        }

        $this->items[$name] = $item;
    }

    public function getDescription()
    {
        return 'Generic SmokeTest to check connectivity.';
    }

    public function run()
    {
        $smokeTestOutput = new SmokeTestOutput();
        $exitCode = SmokeTestOutput::OUTPUT_CODE_SUCCESS;

        // if there are no items to check their connectivity this smoke test passes
        if (empty($this->items)) {
            $smokeTestOutput->setCode($exitCode);
            $smokeTestOutput->addMessage('I\'m useless... There are no items which needs to check their connectivity.');

            return $smokeTestOutput;
        }

        foreach ($this->items as $name => $item) {
            $smokeTestOutputForItem = $item->checkConnectivityForSmokeTest();

            if (!$smokeTestOutputForItem->isOK()) {
                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
            }

            $messages = $smokeTestOutputForItem->getMessages();
            foreach ($messages as $message) {
                $smokeTestOutput->addMessage(
                    sprintf(
                        '[%s]: %s',
                        $name,
                        $message
                    )
                );
            }
        }

        if ($exitCode === SmokeTestOutput::OUTPUT_CODE_SUCCESS) {
            $smokeTestOutput->addMessage('Connectivity checked.');
        }

        $smokeTestOutput->setCode($exitCode);

        return $smokeTestOutput;
    }
}