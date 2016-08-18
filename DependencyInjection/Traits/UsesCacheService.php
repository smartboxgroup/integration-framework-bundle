<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\CoreBundle\Utils\Cache\CacheServiceInterface;

trait UsesCacheService
{
    /**
     * @var CacheServiceInterface
     */
    protected $cacheService;

    /**
     * @return CacheServiceInterface
     */
    public function getCacheService()
    {
        return $this->cacheService;
    }

    /**
     * @param CacheServiceInterface $cacheService
     */
    public function setCacheService(CacheServiceInterface $cacheService)
    {
        $this->cacheService = $cacheService;
    }
}
