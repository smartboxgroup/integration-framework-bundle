<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Tools\CentralDesktop\Stomp\ConnectionFactory;

use Smartbox\CoreBundle\Tests\Utils\Cache\FakeCacheService;
use Smartbox\CoreBundle\Tests\Utils\Cache\FakeCacheServiceSpy;
use Smartbox\CoreBundle\Utils\Cache\CacheServiceInterface;
use Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\ConnectionFactory\CachedFailoverConnectionStrategy;
use Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\ConnectionFactory\InfiniteIteratorWithAccessToLastAvailableHost;

/**
 * Class CachedFailoverConnectionStrategyTest
 */
class CachedFailoverConnectionStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FakeCacheServiceSpy
     */
    protected $fakeCacheServiceSpy;

    /**
     * @var CacheServiceInterface
     */
    protected $fakeCacheService;

    protected function setUp()
    {
        $this->fakeCacheServiceSpy = new FakeCacheServiceSpy();
        $this->fakeCacheService = new FakeCacheService($this->fakeCacheServiceSpy);
    }

    public function dataProviderForInvalidHost()
    {
        return [
            'Malformed URI' => [
                'malformed_uri',
            ],
            'Malformed URI - with protocol but without host' => [
                'failover://',
            ],
            'Invalid protocol - unknown protocol' => [
                'wrong_protocol://localhost:636363',
            ],
            'Invalid protocol - unsupported protocol' => [
                'tcp://localhost:636363',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForInvalidHost
     *
     * @param $host
     */
    public function testConstructorForInvalidArguments($host)
    {
        $this->expectException(\InvalidArgumentException::class);
        new CachedFailoverConnectionStrategy($this->fakeCacheService, $host);
    }


    public function dataProviderForValidHost()
    {
        return [
            'failover protocol with one host' => [
                'failoverHost' => 'failover:(tcp://localhost:636363)',
                'resolvedHosts' => ['tcp://localhost:636363'],
                'randomize' => true
            ],
            'failover protocol with serveral hosts and randomize=true (default)' => [
                'failoverHost' => 'failover:(tcp://fake_host_1,tcp://localhost:636363,tcp://fake_host_2)',
                'resolvedHosts' => ['tcp://fake_host_1', 'tcp://localhost:636363', 'tcp://fake_host_2'],
                'randomize' => true
            ],
            'failover protocol with serveral hosts and randomize=false' => [
                'failoverHost' => 'failover:(tcp://fake_host_1,tcp://localhost:636363,tcp://fake_host_2)?randomize=false',
                'resolvedHosts' => ['tcp://fake_host_1', 'tcp://localhost:636363', 'tcp://fake_host_2'],
                'randomize' => false
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForValidHost
     *
     * @param $failoverHost
     * @param array $resolvedHosts
     * @param $randomize
     */
    public function testConstructorForValidArguments($failoverHost, array $resolvedHosts, $randomize)
    {
        $cachedFailoverConnectionStrategy = new CachedFailoverConnectionStrategy($this->fakeCacheService, $failoverHost);
        $infiniteHostIterator = $cachedFailoverConnectionStrategy->getHostIterator();

        $this->assertInstanceOf(
            InfiniteIteratorWithAccessToLastAvailableHost::class,
            $infiniteHostIterator
        );

        $hostIterator = $infiniteHostIterator->getInnerIterator();

        if ($randomize) { // hosts will be shuffled but should exist in resolved hosts
            $this->assertEmpty(
                array_diff(iterator_to_array($hostIterator, true), $resolvedHosts),
                'All hosts from the failover transport should be resolved and exist in the hosts iterator.'
            );
        } else { // all hosts should be resolved in the same order
            $this->assertEquals(
                $resolvedHosts,
                iterator_to_array($hostIterator, true),
                'All hosts from the failover transport should be resolved and exist in the hosts iterator in the same order as they were applied.'
            );
        }
    }

    public function testNotifyAboutSuccessfulConnection()
    {
        $failoverHost = 'failover:(tcp://fake_host_1,tcp://localhost:636363,tcp://fake_host_2)?randomize=false';
        $cachedFailoverConnectionStrategy = new CachedFailoverConnectionStrategy(
            $this->fakeCacheService,
            $failoverHost
        );
        $infiniteHostIterator = $cachedFailoverConnectionStrategy->getHostIterator();
        $infiniteHostIterator->next();
        $connectedHost = $infiniteHostIterator->current();
        $cachedFailoverConnectionStrategy->notifyAboutSuccessfulConnection();

        $this->assertSame(
            $connectedHost,
            $infiniteHostIterator->getLastAvailableHost(),
            'After successfull connection notification connected host should be the same as last accessed host.'
        );

        $fakeCacheServiceSpyLog = [
            [
                'method' => 'exists',
                'arguments' => [CachedFailoverConnectionStrategy::CACHE_KEY_FOR_LAST_ACTIVE_HOST],
                'result' => false,
            ],
            [
                'method' => 'set',
                'arguments' => [CachedFailoverConnectionStrategy::CACHE_KEY_FOR_LAST_ACTIVE_HOST, $connectedHost, null],
                'result' => true,
            ]
        ];
        $this->assertEquals(
            $fakeCacheServiceSpyLog,
            $this->fakeCacheServiceSpy->getLog(),
            'Log for cached failover connection strategy should contain all requests in log in the same order'
        );
    }
}
