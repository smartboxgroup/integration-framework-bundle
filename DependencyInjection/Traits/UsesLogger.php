<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Psr\Log\LoggerInterface;

/**
 * Trait UsesLogger.
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
