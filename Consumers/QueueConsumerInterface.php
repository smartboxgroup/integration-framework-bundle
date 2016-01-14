<?php

namespace Smartbox\Integration\FrameworkBundle\Consumers;

use Smartbox\Integration\FrameworkBundle\Drivers\Queue\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Handlers\HandlerInterface;

/**
 * Interface QueueConsumerInterface
 * @package Smartbox\Integration\FrameworkBundle\Consumers
 */
interface QueueConsumerInterface
{
    /**
     * @return QueueDriverInterface
     */
    public function getQueueDriver();

    /**
     * @param QueueDriverInterface $queueDriver
     */
    public function setQueueDriver($queueDriver);

    /**
     * @return mixed
     */
    public function stop();

    /**
     * @param $count
     */
    public function setExpirationCount($count);

    /**
     * @return int
     */
    public function getExpirationCount();

    /**
     * @return bool
     */
    public function shouldStop();

    /**
     * @param HandlerInterface $handler
     */
    public function setHandler(HandlerInterface $handler);

    /**
     * @return HandlerInterface
     */
    public function getHandler();

    /**
     * @param string $queue
     */
    public function consume($queue);
}
