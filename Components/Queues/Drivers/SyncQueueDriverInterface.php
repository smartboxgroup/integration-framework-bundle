<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;

interface SyncQueueDriverInterface extends QueueDriverInterface
{
    /**
     * Returns true if a subscription already exists, false otherwise.
     *
     * @return bool
     */
    public function isSubscribed();

    /**
     * Creates a subscription to the given $queue, allowing to receive messages from it.
     *
     * @param string $queue Queue to subscribe
     */
    public function subscribe(string $queue);

    /**
     * Destroys the created subscription with a queue.
     */
    public function unSubscribe();

    /**
     * Returns One Serializable object from the queue.
     *
     * It requires to subscribe previously to a specific queue
     *
     * @return QueueMessageInterface|null
     */
    public function receive();

    /**
     * Clean all the opened resources, must be called just before terminating the current request.
     */
    public function destroy();
}
