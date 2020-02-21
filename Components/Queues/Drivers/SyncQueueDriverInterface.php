<?php


namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;


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
    public function subscribe($queue);

    /**
     * Destroys the created subscription with a queue.
     */
    public function unSubscribe();

    /**
     * Returns One Serializable object from the queue.
     *
     * It requires to subscribe previously to a specific queue
     *
     * @return \Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface|null
     *
     * @throws \Exception
     */
    public function receive();

    /**
     * Acknowledges the processing of the last received object.
     *
     * The object should be removed from the queue.
     */
    public function ack();

    /**
     * Acknowledges a failure on processing the last received object.
     *
     * The object could be moved to the DLQ or be delivered to another subscription for retrial
     */
    public function nack();
}