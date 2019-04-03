<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

class QueueManager
{
    /**
     * One or more AMQP connections. If one of the connection fail, the manager will try the next one.
     *
     * @var \AMQPConnection[]
     */
    private $connections = [];

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

    /**
     * QueueManager constructor.
     *
     * @param \AMQPConnection[] $connections
     */
    public function __construct(array $connections = [])
    {
        $this->connections = $connections;

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
            if (empty($this->connections)) {
                throw new \InvalidArgumentException('You have to specify at least one connection.');
            }

            $this->connection = null;
            $tested = [];

            foreach ($this->connections as $connection) {
                try {
                    $connection->connect();
                    $this->connection = $connection;
                    break;
                } catch (\AMQPConnectionException $e) {
                    $tested[] = "{$connection->getHost()}: {$e->getMessage()}";
                }
            }

            if (!$this->connection) {
                throw new \RuntimeException(sprintf('Unable to connect to any of the following hosts:%s%s', PHP_EOL, implode(PHP_EOL, $tested)));
            }

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
        if ($this->connection) {
            $this->connection->disconnect();
        }
        $this->connection = null;
    }

    /**
     * Returns true if a connection already exists with the queueing system, false otherwise.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connection && $this->connection->isConnected();
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

    public function addConnection(\AMQPConnection $connection)
    {
        $this->connections[] = $connection;

        return $this;
    }
}
