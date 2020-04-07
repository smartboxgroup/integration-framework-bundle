<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;

interface QueueDriverInterface extends SerializableInterface
{
    const DEFAULT_TTL = 86400;

    const HEADER_TTL = 'ttl';
    const HEADER_EXPIRES = 'expires';
    const HEADER_TYPE = 'type';
    const HEADER_PRIORITY = 'priority';

    /**
     * Configures the driver.
     */
    public function configure(string $host, string $username, string $password, string $vhost = null);

    /**
     * Opens a connection with a queuing system.
     */
    public function connect();

    /**
     * Destroys the connection with the queuing system.
     */
    public function disconnect();

    /**
     * Returns true if a connection already exists with the queing system, false otherwise.
     */
    public function isConnected(): bool;

    /**
     * Acknowledges the message in the message broker. $messageId is nullable for backwards compatibility with the
     * SyncQueueDriverInterface. In practice, unless your driver is keeping track of messages, $message should always
     * be passed to this function.
     */
    public function ack(QueueMessageInterface $message = null);

    /**
     * Negative acknowledgement of a message.
     *
     * @see ack() for extra information about this function.
     */
    public function nack(QueueMessageInterface $message = null);

    /**
     * Publish the message to the broker.
     */
    public function send(string $destination, string $body = '', array $headers = []): bool;

    public function createQueueMessage(): QueueMessageInterface;
}
