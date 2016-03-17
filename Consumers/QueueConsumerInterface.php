<?php

namespace Smartbox\Integration\FrameworkBundle\Consumers;

use Smartbox\Integration\FrameworkBundle\Drivers\Queue\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Handlers\HandlerInterface;

/**
 * Interface QueueConsumerInterface
 * @package Smartbox\Integration\FrameworkBundle\Consumers
 */
interface QueueConsumerInterface extends ConsumerInterface
{
    /**
     * @return QueueDriverInterface
     */
    public function getQueueDriver();

    /**
     * @param QueueDriverInterface $queueDriver
     */
    public function setQueueDriver($queueDriver);
}
