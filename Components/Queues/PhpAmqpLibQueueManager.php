<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class PhpAmqpLibQueueManager
{
    use UsesSmartesbHelper;
    use UsesExceptionHandlerTrait;


    /**
     * Message Id
     * @var int
     */
    private $id;

    /**
     * One or more AMQP connections. If one of the connection fail, the manager will try the next one.
     * @var array AMQPStreamConnection
     */
    private $connections = [];

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var \AMQPExchange
     */
    private $exchange;

    /**
     * @var \AMQPQueue
     */
    private $queue;

    /**
     * @var AMQPMessage
     */
    private $message;

    /**
     * PhpAmqpLibQueueManager constructor.
     * @param \AMQPConnection[] $connections
     */
    public function __construct($connections = null, string $format = 'json')
    {
        $this->connections = $connections;
        $this->format = $format;
    }

    /**
     * PhpAmqpLibQueueMessage destructor
     * @throws \Exception
     * @return
     */
    public function __destruct()
    {
        try {
            if ($this->isConnected()) {
                $this->disconnect();
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Opens a connection with a queuing system.
     * @param bool $shuffle Shuffle connections to avoid connecting to the seame endpoint everytime
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
        }

        if (!$this->channel) {
            $this->declareChannel();
        }
    }

    /**
     * Destroys the connection with the queuing system.
     */
    public function disconnect()
    {
        try {
            if ($this->isConnected()) {
                $this->connections[0]->close();
            }
            $this->connection = null;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Returns true if a connection already exists with the queueing system, false otherwise.
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connections[0]  ? $this->connections[0]->isConnected() : false;
    }


    /**
     * Add a connection to the browker
     * @param $connection
     * @return $this
     */
    public function addConnection($connection)
    {
        $this->connections[] = $connection;

        return $this;
    }

    /**
     * Returns all the connections available
     * @return \AMQPConnection[]|array|null
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Return the Queue name if there is already a queue. Otherwise it declares a new queue
     *
     * @param string $queueName
     * @return \AMQPQueue
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPException
     * @throws \AMQPQueueException
     */
    public function getQueue(string $queueName): \AMQPQueue
    {
        if (!$this->queue) {
            $this->connect();
            $this->declareQueue($queueName);
        }
        return $this->queue ;
    }

    /**
     * @return mixed
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return PhpAmqpLibQueueManager
     */
    public function setId($id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Shutdown the connection
     * @throws \Exception
     */
    public function shutdown(): bool
    {
        try {
            if ($this->channel->getConnection()) {
                $this->channel->getConnection()->close();
                $this->channel->close();
                return true;
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
}