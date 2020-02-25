<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

/**
 * Interface AsyncQueueDriverInterface
 * @package Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers
 */
interface AsyncQueueDriverInterface extends QueueDriverInterface
{
    /**
     * Declares a queue to be consumed and installs an optional callback function to be called once the worker is put
     * in wait state and a message arrives.
     *
     * @param string $consumerTag
     * @param string $queueName
     * @param callable|null $callback
     */
    public function consume(string $consumerTag, string $queueName, callable $callback = null);

    /**
     * Triggers the callback if there's a message available to be consumed. If there's none, returns control to the
     * calling function. Must not stop the execution of the application if there's nothing to be consumed.
     */
    public function waitNoBlock();

    /**
     * Triggers the callback if there's a message available to be consumed, otherwise puts the worker in wait state
     * until a message arrives.
     */
    public function wait();
}
