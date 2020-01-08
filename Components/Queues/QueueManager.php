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
     *
     * @param bool $shuffle Shuffle connections to avoid connecting to the seame endpoint everytime
     *
     * @throws \AMQPException
     */
    public function connect(bool $shuffle = true)
    {
        if (!$this->isConnected()) {
            if (empty($this->connections)) {
                throw new \InvalidArgumentException('You have to specify at least one connection.');
            }

            if ($shuffle) {
                shuffle($this->connections);
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
            $this->channel->setPrefetchCount(1);

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
     * @param string $name      The name of the que
     * @param int    $flag      See AMQPQueue::setFlags()
     * @param array  $arguments See AMQPQueue::setArguments()
     *
     * @return \AMQPQueue
     *
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function getQueue(string $name, int $flag = AMQP_DURABLE, array $arguments = []): \AMQPQueue
    {
        $this->connect();
        $queue = new \AMQPQueue($this->channel);
        $queue->setName($name);
        $queue->setFlags($flag);
        $queue->setArguments($arguments);
        $queue->declareQueue();

        return $queue;
    }

    public function send(string $queueName, string $message, array $headers = [])
    {
        $this->connect();

        $arguments = [];
        foreach ($headers as $key => $value) {
            if (0 === strpos(strtolower($key), 'x-')) {
                $arguments[$key] = $value;
            }
        }

        $this->getQueue($queueName, AMQP_DURABLE, $arguments);

        return $this->exchange->publish($message, $queueName, AMQP_NOPARAM, $headers);
    }

    public function addConnection($connection)
    {
        $this->connections[] = $connection;

        return $this;
    }
}
