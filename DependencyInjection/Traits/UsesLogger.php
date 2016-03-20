<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Psr\Log\LoggerInterface;

/**
 * Class UsesLogger
 * @package Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits
 */
trait UsesLogger
{
    /** @var  LoggerInterface */
    protected $logger;

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
}
