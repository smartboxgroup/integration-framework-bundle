<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Components\DB\Dbal;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
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

        $stmt = $this->getMockBuilder(Statement::class)->getMock();
        $stmt->expects($this->once())
            ->method('columnCount')
            ->will($this->returnValue(1));
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue(['test' => 1]));

        $dbal = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbal->expects($this->once())
            ->method('executeQuery')
            ->will($this->returnValue($stmt));

        $doctrine = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->once())
            ->method('getConnection')
            ->with($connectionName)
            ->will($this->returnValue($dbal));

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
