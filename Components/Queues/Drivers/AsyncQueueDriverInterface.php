<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;

/**
 * Interface AsyncQueueDriverInterface
 * @package Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers
 */
interface AsyncQueueDriverInterface extends QueueDriverInterface
{
    /**
     * This field specifies the prefetch window size in octets.
     * The server will send a message in advance if it is equal to or smaller in size than the available prefetch size
     * (and also falls into other prefetch limits).
     * May be set to zero, meaning "no specific limit", although other prefetch limits may still apply.
     * The prefetch-size is ignored if the no-ack option is set.
     */
    const PREFETCH_SIZE = null;

    /**
     * Represents the amount of message to consume by iteration
     */
    const PREFETCH_COUNT = 1;

    /*
     * To use the default exchange pass an empty string
     */
    const EXCHANGE_NAME = '';

    /**
     * Returns a serialized message from the queue
     *
     * @param string $consumerTag
     * @param string $queueName
     * @param callable|null $callback
     * @return mixed
     */
    public function consume(string $consumerTag, string $queueName, ?callable $callback = null);

    /**
     * Verifies if the channel is consuming a message
     * If there is a message to consume it calls the consume callback function
     * If there is no message to consume it will put the worker in a wait state
     *
     * @throws \Exception
     */
    public function wait();

    /**
     * @param int $deliveryTag
     * @return mixed
     */
    public function ack(int $deliveryTag);

    /**
     * @param int $deliveryTag
     * @return mixed
     */
    public function nack(int $deliveryTag);
}