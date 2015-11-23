<?php
namespace Smartbox\Integration\FrameworkBundle\Drivers\Queue;


use Smartbox\Integration\FrameworkBundle\Messages\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Messages\Queues\QueueMessageInterface;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ArrayQueueDriver
 * @package Smartbox\Integration\FrameworkBundle\Drivers\Queue
 */
class ArrayQueueDriver implements QueueDriverInterface{

    static $array = array();

    protected $connected = false;
    protected $subscribedQueue = false;
    protected $unacknowledgedFrame = null;

    /**
     * @return array
     */
    public function getArrayForQueue($queue)
    {
        if(!array_key_exists($queue,self::$array)){
            self::$array[$queue] = [];
        }

        return self::$array[$queue];
    }

    /**
     * Configures the driver
     *
     * @param string $uri URI of the queuing system
     * @param string $username Username to connect to the queuing system
     * @param string $password Password to connect to the queuing system
     */
    public function configure($host, $username, $password, $format = '')
    {
        self::$array = [];
    }

    /**
     * Opens a connection with a queuing system
     */
    public function connect()
    {
        $this->connected = true;
    }

    /**
     * Destroys the connection with the queuing system
     */
    public function disconnect()
    {
        $this->connected = false;
    }

    /**
     * Returns true if a connection already exists with the queing system, false otherwise
     * @return boolean
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * Returns true if a subscription already exists, false otherwise
     * @return boolean
     */
    public function isSubscribed()
    {
        return !empty($this->subscribedQueue);
    }

    /**
     * Creates a subscription to the given $queue, allowing to receive messages from it
     *
     * @param string $queue Queue to subscribe
     *
     * @param string|null $selector If supported, it is an expression filters the messages on the queue.
     */
    public function subscribe($queue, $selector = null)
    {
        $this->subscribedQueue = $queue;
    }

    /**
     * Destroys the created subscription with a queue
     */
    public function unSubscribe()
    {
        $this->subscribedQueue = false;
    }

    /**
     * Acknowledges the processing of the last received object.
     *
     * The object should be removed from the queue.
     */
    public function ack()
    {
        $this->unacknowledgedFrame = false;
    }

    /**
     * Acknowledges a failure on processing the last received object
     *
     * The object could be moved to the DLQ or be delivered to another subscription for retrial
     */
    public function nack()
    {
        $this->unacknowledgedFrame = false;
    }

    /** {@inheritDoc} */
    public function send(QueueMessageInterface $message) {
        if(!array_key_exists($message->getQueue(),self::$array)){
            self::$array[$message->getQueue()] = [];
        }

        self::$array[$message->getQueue()][] = $message;

        return true;
    }

    /** {@inheritDoc} */
    public function receive()
    {
        if(array_key_exists($this->subscribedQueue,self::$array) && !empty(self::$array[$this->subscribedQueue])){
            $this->unacknowledgedFrame = array_shift(self::$array[$this->subscribedQueue]);
        }

        return $this->unacknowledgedFrame;
    }

    /**
     * @return QueueMessageInterface
     */
    public function createQueueMessage()
    {
        /**
         * This driver will ignore all the headers so it can use any message that implements QueueMessageInterface
         */
        return new QueueMessage();
    }
}