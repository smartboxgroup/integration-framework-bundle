<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageHandlerInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\PhpAmqpLibDriver;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Tools\Helper\SmartesbHelper;

class PhpAmqpLibQueueManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use UsesSmartesbHelper;
    use UsesExceptionHandlerTrait;

    const PREFETCH_SIZE = 1;
    const PREFETCH_COUNT = 1;

    // To use the default exchange pass an empty string
    const EXCHANGE_NAME = '';
    
    private $id;
    
    /**
     * One or more AMQP connections. If one of the connection fail, the manager will try the next one.
     *
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

    public $max;

    public $shoudlStop;

    public $format;

    public $serializer;

    public $endpoint;

    /**
     * QueueManager constructor.
     *
     * @param \AMQPConnection[] $connections
     */
    public function __construct($connections = null, int $max = -1, string $format = 'json', SerializableInterface $serializer = null)
    {
        $this->connections = $connections;
        $this->shoudlStop = false;
        $this->max = $max;
        $this->format = $format;
        $this->serializer = $serializer ?? SerializerBuilder::create()->build();
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
        if ($connection = $this->channel->getConnection()) {
            $connection->close();
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
        return $this->connections[0]  ? $this->connections[0]->isConnected() : false;
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
    public function declareQueue(string $name, int $flag = AMQP_DURABLE, array $arguments = [])
    {
        if (!$this->channel) {
            throw new \AMQPChannelException('There is no channel available');
        }

        $this->queue = $name;
        $durable = $flag === AMQP_DURABLE ? true : false;
        list($queueName, $this->expirationCount, $consumerCount) = $this->channel->queue_declare($this->queue, false, $durable, false, false, false, new AMQPTable([$arguments]));
    }

    public function publish(AMQPMessage $message)
    {
        $this->channel->basic_publish($message, self::EXCHANGE_NAME, $this->queue);
    }

    public function addConnection($connection)
    {
        $this->connections[] = $connection;

        return $this;
    }

    public function getConnections()
    {
        return $this->connections;
    }

    //Declare channel
    public function declareChannel()
    {
        $this->channel = reset($this->connections)->channel();
        $this->channel->basic_qos(null, self::PREFETCH_COUNT, true);
    }

    //AMQP Exchange is the publishing mechanism
    public function declareExchange($exchange)
    {
        $this->exchange = $exchange;
        $this->exchange->setName(self::EXCHANGE_NAME);
        $this->channel->exchange_declare($this->exchange->getName(), AMQPExchangeType::DIRECT, false, true, false);
        $this->channel->queue_bind($this->queue, $this->exchange->getName(), $this->queue);
    }

    public function getQueue(string $queueName)
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return PhpAmqpLibQueueManager
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function consume(string $consumerTag)
    {
        $callback = function($message) {
            $this->log('A message was received on {time}');
            $this->log('Message Body:' . $message->body);

            // Send a message with the string "quit" to cancel the consumer.
            if ($message->body === 'quit' || $this->shoudlStop) {
                $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
                return false;
            }

            $message = $this->deserializeMessage($message);
            $this->dispatchMessage($message);
            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        };

        $this->channel->basic_consume($this->queue, $consumerTag, false, false, false, false, $callback);
        $message = $this->channel->basic_get($this->queue);

        return $message;
    }

    public function shutdown()
    {
        $this->channel->getConnection()->close();
        $this->channel->close();
    }

    public function isConsuming()
    {
        try {
            echo 'Enter wait.' . PHP_EOL;
            while ($this->channel->is_consuming()) {
                $this->channel->wait(null, true);
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            return;
        } finally {
            return;
        }
    }

    /**
     * @param string $consumerTag
     * @return mixed
     */
    public function stopConsumer(string $consumerTag)
    {
        return $this->channel->basic_cancel($consumerTag, false, false);
    }

    public function getExpirationCount()
    {
        return $this->expirationCount;
    }

    public function dispatchMessage(AMQPMessage $message)
    {
        if (!$this->endpoint) {
            throw new \Exception('Endpoint is undefined');
        }

        try {
            $this->endpoint->getHandler()->handle($message, $this->endpoint);
        } catch (\Exception $exception) {
            $this->getExceptionHandler()($exception, ['headers' => $message->getHeaders(), 'body' => $message->getBody()]);
        }
    }

    /**
     * @param AMQPMessage
     * @return  \Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface|QueueMessageInterface $message
     */
    public function deserializeMessage(AMQPMessage $message)
    {
        try {
            return $this->serializer->deserialize($message->getBody(), SerializableInterface::class, $this->format);
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage());
//            $this->getExceptionHandler()($exception, ['headers' => $message->getHeaders(), 'body' => $message->getBody()]);
        }
    }

    private function log(string $message, array $ctx = [])
    {
        if (null === $this->logger) {
            return;
        }

        $now = new \DateTime();
        $ctx['time'] = $now->format('Y-m-d H:i:s.u');
        $this->logger->info($message, $ctx);
    }

    /**
     * @param \Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface $message
     *
     * @return bool
     */
    private function isQueueMessage($message): bool
    {
        return $message instanceof QueueMessageInterface && !($this->endpoint->getHandler() instanceof QueueMessageHandlerInterface);
    }

    public function setEndpoint(Endpoint $endpoint)
    {
        $this->endpoint = $endpoint;
    }
}