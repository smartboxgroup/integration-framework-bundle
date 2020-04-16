<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class ArrayQueueDriver.
 */
class ArrayQueueDriver extends Service implements SyncQueueDriverInterface
{
    public static $array = [];

    protected $connected = false;
    protected $subscribedQueue = false;
    protected $unacknowledgedFrame;

    /**
     * @param $queue
     *
     * @return array
     */
    public function getArrayForQueue($queue)
    {
        if (!array_key_exists($queue, self::$array)) {
            self::$array[$queue] = [];
        }

        return self::$array[$queue];
    }

    /**
     * Configures the driver.
     *
     * @param string $username Username to connect to the queuing system
     * @param string $password Password to connect to the queuing system
     */
    public function configure(string $host, string $username, string $password, string $vhost = null)
    {
        self::$array = [];
    }

    /**
     * Opens a connection with a queuing system.
     */
    public function connect()
    {
        $this->connected = true;
    }

    /**
     * Destroys the connection with the queuing system.
     */
    public function disconnect()
    {
        $this->connected = false;
    }

    /**
     * Returns true if a connection already exists with the queing system, false otherwise.
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Returns true if a subscription already exists, false otherwise.
     *
     * @return bool
     */
    public function isSubscribed()
    {
        return !empty($this->subscribedQueue);
    }

    /**
     * Creates a subscription to the given $queue, allowing to receive messages from it.
     *
     * @param string      $queue    Queue to subscribe
     * @param string|null $selector If supported, it is an expression filters the messages on the queue
     */
    public function subscribe(string $queue, $selector = null)
    {
        $this->subscribedQueue = $queue;
    }

    /**
     * Destroys the created subscription with a queue.
     */
    public function unSubscribe()
    {
        $this->subscribedQueue = false;
    }

    /**
     * Acknowledges the processing of the last received object.
     * The object should be removed from the queue.
     */
    public function ack(QueueMessageInterface $message = null)
    {
        $this->unacknowledgedFrame = false;
    }

    /**
     * Acknowledges a failure on processing the last received object.
     * The object could be moved to the DLQ or be delivered to another subscription for retrial.
     */
    public function nack(QueueMessageInterface $message = null)
    {
        $this->unacknowledgedFrame = false;
    }

    /**
     * {@inheritdoc}
     */
    public function send(string $destination, string $body = '', array $headers = []): bool
    {
        if (!array_key_exists($destination, self::$array)) {
            self::$array[$destination] = [];
        }

        self::$array[$destination][] = $body;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function receive()
    {
        if (array_key_exists($this->subscribedQueue, self::$array) && !empty(self::$array[$this->subscribedQueue])) {
            $this->unacknowledgedFrame = array_shift(self::$array[$this->subscribedQueue]);
        }

        return $this->unacknowledgedFrame;
    }

    /**
     * Clean all the opened resources, must be called just before terminating the current request.
     */
    public function destroy()
    {
        // TODO: Implement doDestroy() method.
        // I have no time to do destroy the world.
    }
}
