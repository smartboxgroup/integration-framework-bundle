<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;

interface QueueDriverInterface extends SerializableInterface
{
    const FORMAT_JSON = 'json';
    const FORMAT_XML = 'xml';
    const DEFAULT_TTL = 86400;

    const HEADER_TTL = 'ttl';
    const HEADER_EXPIRES = 'expires';
    const HEADER_TYPE = 'type';
    const HEADER_PRIORITY = 'priority';

    /**
     * Configures the driver.
     *
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string|null $vhost
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
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Acknowledges the message in the message broker. $messageId is nullable for backwards compatibility with the
     * SyncQueueDriverInterface. In practice, unless your driver is keeping track of messages, $message should always
     * be passed to this function.
     *
     * @param QueueMessageInterface|null $message
     */
    public function ack(QueueMessageInterface $message = null);

    /**
     * Negative acknowledgement of a message.
     *
     * @see ack() for extra information about this function.
     *
     * @param QueueMessageInterface|null $message
     */
    public function nack(QueueMessageInterface $message = null);

    /**
     * @param QueueMessageInterface $message
     * @param string|null $destination
     * @param array $arguments
     *
     * @return bool
     */
    public function send(QueueMessageInterface $message, $destination = null, array $arguments = []): bool;

    /**
     * @return QueueMessageInterface
     */
    public function createQueueMessage(): QueueMessageInterface;

    /**
     * Returns the format used on serialize/deserialize function.
     *
     * @return string
     */
    public function getFormat(): string;

    /**
     * Set the format used on serialize/deserialize function.
     *
     * @param string|null $format
     */
    public function setFormat(string $format);
}
