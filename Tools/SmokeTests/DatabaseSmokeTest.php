<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\SmokeTests;

use Doctrine\Common\Persistence\ConnectionRegistry;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;
use Smartbox\CoreBundle\Utils\SmokeTest\SmokeTestInterface;

/**
 * Test availability of defined databases.
 */
class DatabaseSmokeTest implements SmokeTestInterface
{
    /**
     * @var ConnectionRegistry
     */
    private $registry;

    /**
     * DatabaseSmokeTest constructor.
     *
     * @param ConnectionRegistry $registry
     */
    public function __construct(ConnectionRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return sprintf(
            'Test database connectivity for connections: %s.',
            \implode(', ', \array_keys($this->registry->getConnectionNames()))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $output = new SmokeTestOutput();
        $output->setCode(SmokeTestOutput::OUTPUT_CODE_SUCCESS);

        foreach ($this->registry->getConnections() as $key => $connection) {
            /* @var \Doctrine\DBAL\Connection $connection */
            try {
                $schemaManager = $connection->getSchemaManager();
                $count = \count($schemaManager->listTables());
                $output->addSuccessMessage("Connection to \"$key\" database checked, found $count table(s).");
            } catch (\Exception $e) {
                $output->setCode(SmokeTestOutput::OUTPUT_CODE_FAILURE);
                $output->addFailureMessage("Connection to \"$key\" database failed: \"{$e->getMessage()}\".");
            }
        }

        return $output;
    }
}
