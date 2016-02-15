<?php
namespace Smartbox\Integration\FrameworkBundle\Drivers\Queue;


use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Queues\QueueMessageInterface;

interface QueueDriverInterface extends SerializableInterface {
    const FORMAT_JSON = 'json';
    const FORMAT_XML = 'xml';
    const DEFAULT_TTL = 86400;

    const HEADER_TTL = 'ttl';
    const HEADER_EXPIRES = 'expires';
    const HEADER_TYPE = 'type';
    const HEADER_PRIORITY = 'priority';

    /**
     * Configures the driver
     *
     * @param $host
     * @param string $username Username to connect to the queuing system
     * @param string $password Password to connect to the queuing system
     * @param string $format
     */
    public function configure($host, $username, $password, $format = self::FORMAT_JSON);

    /**
     * Opens a connection with a queuing system
     */
    public function connect();

    /**
     * Destroys the connection with the queuing system
     */
    public function disconnect();

    /**
     * Returns true if a connection already exists with the queing system, false otherwise
     * @return boolean
     */
    public function isConnected();

    /**
     * Returns true if a subscription already exists, false otherwise
     * @return boolean
     */
    public function isSubscribed();

    /**
     * Creates a subscription to the given $queue, allowing to receive messages from it
     *
     * @param string $queue             Queue to subscribe
     *
     */
    public function subscribe($queue);

    /**
     * Destroys the created subscription with a queue
     */
    public function unSubscribe();

    /**
     * Acknowledges the processing of the last received object.
     *
     * The object should be removed from the queue.
     */
    public function ack();


    /**
     * Acknowledges a failure on processing the last received object
     *
     * The object could be moved to the DLQ or be delivered to another subscription for retrial
     */
    public function nack();

    /**
     * @param QueueMessageInterface $message
     * @return boolean
     */
    public function send(QueueMessageInterface $message);


    /**
     * Returns One Serializable object from the queue
     *
     * It requires to subscribe previously to a specific queue
     *
     * @return QueueMessageInterface|null
     * @throws \Exception
     */
    public function receive();

    /**
     * @return QueueMessageInterface
     */
    public function createQueueMessage();

    /**
     * Clean all the opened resources, must be called just before terminating the current request
     */
    public function doDestroy();
}
