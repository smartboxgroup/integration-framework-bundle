<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\ConnectionFactory;

use Smartbox\CoreBundle\Utils\Cache\CacheServiceInterface;

/**
 * Class supporting ActiveMQ failover transport with cache
 * http://activemq.apache.org/failover-transport-reference.html.
 *
 * Supported transport options:
 *  - randomize=[true|false] Default: true
 */
class CachedFailoverConnectionStrategy implements FactoryI
{
    const TRANSPORT = 'failover';
    const CACHE_KEY_FOR_LAST_ACTIVE_HOST = 'last_active_host_for_CachedFailoverConnectionStrategy';

    /**
     * @var InfiniteIteratorWithAccessToLastAvailableHost
     */
    protected $hostsIterator;
    protected $hosts = [];

    protected $lastActiveHost;

    /**
     * @var CacheServiceInterface
     */
    protected $cacheService;

    /**
     * @param CacheServiceInterface $cacheService
     * @param string                $host
     */
    public function __construct(CacheServiceInterface $cacheService, $host)
    {
        $this->cacheService = $cacheService;
        $transport = parse_url($host, PHP_URL_SCHEME);

        if (false === $transport) {
            throw new \InvalidArgumentException(
                sprintf('Could not resolve transport protocol for invalid URI "%s".', $host)
            );
        }

        if (self::TRANSPORT !== $transport) {
            throw new \InvalidArgumentException(
                sprintf('Expected "%s" transport. Got "%s".', self::TRANSPORT, $transport)
            );
        }

        $this->hosts = $this->resolveFailoverHosts($host);
        $this->hostsIterator = new InfiniteIteratorWithAccessToLastAvailableHost(new \ArrayIterator($this->hosts));
    }

    private function resolveFailoverHosts($host)
    {
        $hostComponents = parse_url($host);

        $hosts = explode(',', trim($hostComponents['path'], '()'));

        $randomize = true;
        if (array_key_exists('query', $hostComponents)) {
            $queryParams = [];
            parse_str($hostComponents['query'], $queryParams);

            if (isset($queryParams['randomize']) && $queryParams['randomize'] === 'false') {
                $randomize = false;
            }
        }

        if ($randomize) {
            shuffle($hosts);
        }

        // host stored in the cache will be used as first
        if ($this->cacheService->exists(self::CACHE_KEY_FOR_LAST_ACTIVE_HOST)) {
            $this->lastActiveHost = $this->cacheService->get(self::CACHE_KEY_FOR_LAST_ACTIVE_HOST);
            $hosts = array_diff($hosts, [$this->lastActiveHost]); // remove host
            array_unshift($hosts, $this->lastActiveHost); // add as the first element in the hosts list
        }

        return $hosts;
    }

    /**
     * Gets the next URL to connect to.
     */
    public function getHostIterator()
    {
        return $this->hostsIterator;
    }

    public function __toString()
    {
        return get_class($this).'('.implode(',', $this->hosts).')';
    }

    /**
     * {@inheritdoc}
     */
    public function notifyAboutSuccessfulConnection()
    {
        $host = $this->hostsIterator->getLastAvailableHost();
        if ($host !== $this->lastActiveHost) {
            $this->cacheService->set(self::CACHE_KEY_FOR_LAST_ACTIVE_HOST, $host, null);
        }
    }
}
