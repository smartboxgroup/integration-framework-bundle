<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\SmokeTests;

use Smartbox\CoreBundle\Utils\SmokeTest\SmokeTestInterface;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutputMessage;

/**
 * Class ConnectivityCheckSmokeTest.
 */
class ConnectivityCheckSmokeTest implements SmokeTestInterface
{
    const TAG_TEST_CONNECTIVITY = 'smartesb.smoke_test.test_connectivity';

    /**
     * @var null|string
     */
    protected $description = 'Generic SmokeTest to check connectivity.';

    /**
     * @var CanCheckConnectivityInterface[]
     */
    protected $items = [];

    /**
     * ConnectivityCheckSmokeTest constructor.
     *
     * @param string|null                     $description
     * @param CanCheckConnectivityInterface[] $items
     */
    public function __construct($description = null, $items = [])
    {
        if (null !== $description) {
            $this->description = $description;
        }
        $this->items = $items;
    }

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
        return $this->description;
    }

    public function run()
    {
        $smokeTestOutput = new SmokeTestOutput();
        $exitCode = SmokeTestOutput::OUTPUT_CODE_SUCCESS;

        // if there are no items to check their connectivity this smoke test passes
        if (empty($this->items)) {
            $smokeTestOutput->setCode($exitCode);
            $smokeTestOutput->addInfoMessage('I\'m useless... There are no items which needs to check their connectivity.');

            return $smokeTestOutput;
        }

        foreach ($this->items as $name => $item) {
            try {
                $smokeTestOutputForItem = $item->checkConnectivityForSmokeTest();

                if (!$smokeTestOutputForItem->isOK()) {
                    $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                }

                $messages = $smokeTestOutputForItem->getMessages();
                foreach ($messages as $message) {
                    $messageText = sprintf(
                        '[%s]: %s',
                        $name,
                        $message->getValue()
                    );
                    switch($message->getType()) {
                        case SmokeTestOutputMessage::OUTPUT_MESSAGE_TYPE_INFO:
                            $smokeTestOutput->addInfoMessage($messageText);
                            break;
                        case SmokeTestOutputMessage::OUTPUT_MESSAGE_TYPE_FAILURE:
                            $smokeTestOutput->addFailureMessage($messageText);
                            break;
                        case SmokeTestOutputMessage::OUTPUT_MESSAGE_TYPE_SUCCESS:
                            $smokeTestOutput->addSuccessMessage($messageText);
                            break;
                        default:
                            $smokeTestOutput->addFailureMessage($messageText);
                            break;
                    }
                }
            } catch (\Exception $e) {
                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                $smokeTestOutput->addFailureMessage(
                    sprintf(
                        '[%s]: %s',
                        $name,
                        '['.get_class($e).'] '.$e->getMessage()
                    )
                );
            }
        }

        if ($exitCode === SmokeTestOutput::OUTPUT_CODE_SUCCESS) {
            $smokeTestOutput->addSuccessMessage('Connectivity checked.');
        }

        $smokeTestOutput->setCode($exitCode);

        return $smokeTestOutput;
    }
}
