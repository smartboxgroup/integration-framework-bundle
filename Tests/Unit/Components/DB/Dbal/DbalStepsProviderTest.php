<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Components\DB\Dbal;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Statement;
use Doctrine\DBAL\Result;
use Smartbox\Integration\FrameworkBundle\Components\DB\Dbal\DbalStepsProvider;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableServiceHelper;

class DbalStepsProviderTest extends \PHPUnit\Framework\TestCase
{
    private $dbalStepsProvider;

    protected function setUp()
    {
        $this->dbalStepsProvider = new DbalStepsProvider();
    }

    protected function tearDown()
    {
        $this->dbalStepsProvider = null;
    }

    public function contextProvider()
    {
        return [
            'Context contains a specific database connection name' => [
                'context' => [
                    'options' => [
                        'db_connection_name' => 'dbtest',
                    ],
                ],
                'options' => [
                    'db_connection_name' => 'dbtest',
                ],
                'db_connection_name' => 'dbtest',
            ],
            'Context does not contain specific database connection name' => [
                'context' => [],
                'options' => [],
                'db_connection_name' => null,
            ],
        ];
    }

    /**
     * @dataProvider contextProvider
     *
     * @param array $context
     * @param array $options
     */
    public function testDoctrineGetConnection(array $context, array $options, $connectionName)
    {
        $parameters = [];
        $sql = 'SELECT test FROM test';

        $pdoStatement = $this->createMock(\PDOStatement::class);
        $pdoStatement->expects($this->once())
            ->method('columnCount')
            ->willReturn(1);
        $pdoStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn(['test' => 1]);

        $dbal = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = new Result(new \Doctrine\DBAL\Driver\PDO\Result($pdoStatement), $dbal);
        $dbal->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result);

        $doctrine = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->once())
            ->method('getConnection')
            ->with($connectionName)
            ->willReturn($dbal);

        $confHelper = $this->getMockBuilder(ConfigurableServiceHelper::class)->getMock();
        $confHelper->expects($this->exactly(2))
            ->method('resolve')
            ->will($this->onConsecutiveCalls($parameters, $sql));

        $action = 'execute';
        $actionParams = [
            'sql' => $sql,
            'parameters' => $parameters,
        ];

        $this->dbalStepsProvider->setDoctrine($doctrine);
        $this->dbalStepsProvider->setConfHelper($confHelper);
        $this->dbalStepsProvider->executeStep($action, $actionParams, $options, $context);
    }
}
