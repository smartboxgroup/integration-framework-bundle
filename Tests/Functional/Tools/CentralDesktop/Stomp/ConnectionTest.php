<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Tools\CentralDesktop\Stomp;

use Smartbox\CoreBundle\Tests\Utils\Cache\FakeCacheService;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\ActiveMQConnectionStrategyFactory;
use Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\Connection;
use Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\ConnectionFactory\FactoryI;
use Zend\Code\Reflection\PropertyReflection;

/**
 * Class ConnectionTest.
 */
class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActiveMQConnectionStrategyFactory
     */
    private $connectionFactory;

    public function setUp()
    {
        $this->connectionFactory = new ActiveMQConnectionStrategyFactory(new FakeCacheService());
    }

    public function dataProviderForHostsAndAttempts()
    {
        return [
            'simple strategy' => [
                'host' => 'tcp://localhost',
                'expectedAttempts' => null,
            ],
            'failover strategy' => [
                'host' => 'failover:(tcp://host_1,tcp://host_2,tcp://host_3)',
                'expectedAttempts' => 3,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForHostsAndAttempts
     *
     * @param $host
     * @param $expectedAttempts - for null we use default value set up in Connection object
     */
    public function testCreateConnectionWillSetProperlyTheNumberOfAttempts($host, $expectedAttempts)
    {
        $connectionStrategy = $this->connectionFactory->createConnectionStrategy($host);
        $connection = new Connection($connectionStrategy);

        $propertyReflection = new PropertyReflection(Connection::class, '_attempts');
        $propertyReflection->setAccessible(true);

        if (is_null($expectedAttempts)) {
            $expectedAttempts = $propertyReflection->getValue($connection);
        }

        $this->assertAttributeEquals(
            $expectedAttempts,
            '_attempts',
            $connection,
            sprintf('Property "_attempts should be set to "%s" during Connection object creation.', $expectedAttempts)
        );
    }

    public function dataProviderForWorkingConnection()
    {
        return [
            [
                'hosts' => ['tcp://localhost:61613'],
            ],
            [
                'hosts' => ['tcp://invalid_host_1', 'tcp://localhost:61613', 'tcp://invalid_host_2'],
            ],
            [
                'hosts' => ['tcp://localhost:61613', 'tcp://invalid_host_1', 'tcp://invalid_host_2'],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForWorkingConnection
     *
     * @param $hosts
     *
     * @throws \CentralDesktop\Stomp\Exception
     */
    public function testConnectWillNotifyConnectionFactoryOnSuccessfullConnection(array $hosts)
    {
        /** @var FactoryI|\PHPUnit_Framework_MockObject_MockObject $connectionStrategy */
        $connectionStrategy = $this->getMockBuilder(FactoryI::class)->getMock();
        $connectionStrategy
            ->expects($this->once())
            ->method('notifyAboutSuccessfulConnection')
        ;

        $connectionStrategy
            ->expects($this->atLeastOnce())
            ->method('getHostIterator')
            ->will($this->returnValue(new \ArrayIterator($hosts)))
        ;

        $connection = new Connection($connectionStrategy);
        $connection->connect();
    }

    public function dataProviderForNotWorkingConnection()
    {
        return [
            [
                'host' => 'tcp://invalid_host_1',
            ],
            [
                'host' => 'failover:(tcp://invalid_host_1,tcp://invalid_host_2)',
            ],
            [
                'host' => 'failover:(tcp://invalid_host_1,tcp://invalid_host_2,tcp://invalid_host_3)?randomize=false',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForNotWorkingConnection
     *
     * @param $host
     */
    public function testConnectWillThrowExceptionInCaseOfConnectivityProblems($host)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not connect to');

        $connectionStrategy = $this->connectionFactory->createConnectionStrategy($host);

        $connection = new Connection($connectionStrategy);
        $connection->connect();
    }
}
