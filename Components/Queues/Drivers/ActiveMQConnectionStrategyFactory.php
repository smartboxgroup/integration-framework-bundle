<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use Smartbox\CoreBundle\Utils\Cache\CacheServiceInterface;
use Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\ConnectionFactory\FactoryI;
use Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\ConnectionFactory\SimpleConnectionStrategy;
use Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\ConnectionFactory\CachedFailoverConnectionStrategy;

/**
 * Class ActiveMQConnectionStrategyFactory
 * @package Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers
 */
class ActiveMQConnectionStrategyFactory
{
    const TRANSPORT_TCP = 'tcp';
    const TRANSPORT_FAILOVER = 'failover';

    /**
     * @var CacheServiceInterface
     */
    protected $cacheService;

    public function __construct(CacheServiceInterface $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * @param $host
     * @return FactoryI
     *
     * @throws \RuntimeException
     */
    public function createConnectionStrategy($host)
    {
        $transport = parse_url($host, PHP_URL_SCHEME);

        switch($transport) {
            case self::TRANSPORT_TCP:
                // TCP transport reference
                // http://activemq.apache.org/tcp-transport-reference.html
                return new SimpleConnectionStrategy($host);
            case self::TRANSPORT_FAILOVER:
                // Failover transport reference
                // http://activemq.apache.org/failover-transport-reference.html
                return new CachedFailoverConnectionStrategy($this->cacheService, $host);
            default:
                throw new \RuntimeException(
                    sprintf(
                        'Can not resolve ActiveMQ driver connection strategy for host "%s". Supported transports [%s], got "%s".',
                        $host,
                        implode(', ', [self::TRANSPORT_TCP, self::TRANSPORT_FAILOVER]),
                        $transport
                    )
                );
        }
    }
}
