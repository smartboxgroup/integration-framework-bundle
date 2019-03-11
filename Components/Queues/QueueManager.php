<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

class QueueManager
{
    /**
     * @var \AMQPConnection
     */
    private $connection;

    /**
     * @var \AMQPChannel
     */
    private $channel;

    /**
     * @var \AMQPExchange
     */
    private $exchange;

    public function __construct(\AMQPConnection $connection)
    {
        $this->connection = $connection;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Opens a connection with a queuing system.
     */
    public function connect()
    {
        if (!$this->isConnected()) {
            $this->connection->connect();

            //Create and declare channel
            $this->channel = new \AMQPChannel($this->connection);
            //AMQPC Exchange is the publishing mechanism
            $this->exchange = new \AMQPExchange($this->channel);
        }
    }

    /**
     * Destroys the connection with the queuing system.
     */
    public function disconnect()
    {
        $this->connection->disconnect();
    }

    /**
     * Returns true if a connection already exists with the queing system, false otherwise.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    /**
     * @param string $name
     * @param int    $flag
     *
     * @return \AMQPQueue
     */
    public function getQueue(string $name, int $flag = AMQP_NOPARAM): \AMQPQueue
    {
        $this->connect();
        $queue = new \AMQPQueue($this->channel);
        $queue->setName($name);
        $queue->setFlags($flag);
        $queue->declareQueue();

        return $queue;
    }

    public function send(string $queueName, string $message, array $headers = [])
    {
        $this->connect();

        $this->getQueue($queueName);

        return $this->exchange->publish($message, $queueName, AMQP_NOPARAM, $headers);
    }
}
