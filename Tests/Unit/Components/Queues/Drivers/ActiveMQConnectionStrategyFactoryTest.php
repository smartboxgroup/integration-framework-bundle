<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Components\Queues\Drivers;

use Smartbox\CoreBundle\Tests\Utils\Cache\FakeCacheService;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\ActiveMQConnectionStrategyFactory;
use Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\ConnectionFactory\SimpleConnectionStrategy;
use Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\ConnectionFactory\CachedFailoverConnectionStrategy;

/**
 * Class ActiveMQConnectionStrategyFactoryTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Components\Queues\Drivers
 */
class ActiveMQConnectionStrategyFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActiveMQConnectionStrategyFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->factory = new ActiveMQConnectionStrategyFactory(new FakeCacheService());
    }

    public function dataProviderForConnectionStrategy()
    {
        return [
            'Create simple connection strategy' => [
                'host' => 'tcp://localhost',
                'expectedInstance' => SimpleConnectionStrategy::class,
                'expectedHosts' => ['tcp://localhost']
            ],
            'Create cached failover connection strategy' => [
                'host' => 'failover:(tcp://primary_host:636363,tcp://secondary_host:636363)?randomize=false',
                'expectedInstance' => CachedFailoverConnectionStrategy::class,
                'expectedHosts' => ['tcp://primary_host:636363', 'tcp://secondary_host:636363']
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForConnectionStrategy
     *
     * @param $host
     * @param $expectedInstance
     * @param $expectedHosts
     */
    public function testCreateConnectionStrategy($host, $expectedInstance, $expectedHosts)
    {
        $connectionStrategy = $this->factory->createConnectionStrategy($host);

        $this->assertInstanceOf(
            $expectedInstance,
            $connectionStrategy,
            sprintf(
                'Connection strategy factory should create connection strategy object which is instance of "%s"',
                $expectedInstance
            )
        );

        $hostIterator = $connectionStrategy->getHostIterator();
        if ($hostIterator instanceof \InfiniteIterator) {
            $hostIterator = $hostIterator->getInnerIterator();
        }

        $this->assertEquals(
            $expectedHosts,
            iterator_to_array($hostIterator, true),
            'Iterator returned by the connection strategy object should contain all provided hosts.'
        );
    }
}
