<?php


namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;


interface PurgeableQueueDriverInterface extends QueueDriverInterface
{
    /**
     * Remove every messages in the defined queue.
     *
     * @param string $queue The queue name
     */
    public function purge(string $queue);
}