<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerAwareTrait;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Handler\PhpAmqpHandler;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueProtocol;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class PhpAmqpLibDriver
 * @package Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers
 */
class PhpAmqpLibDriver extends Service implements QueueDriverInterface
{
    use UsesSerializer;
    use UsesExceptionHandlerTrait;
    use UsesSmartesbHelper;
    use LoggerAwareTrait;

    /**
     * Maximum amount of time (in seconds) to wait for a message.
     */
    const WAIT_TIMEOUT = 10;

    /**
     * Maximum amount of time (in seconds) to set as an interval while consuming messages
     */
    const WAIT_PERIOD = 0.2;

    /**
     * Represents the amount of message to consume by iteration
     */
    const PREFETCH_COUNT = 1;

    /*
     * To use the default exchange pass an empty string
     */
    const EXCHANGE_NAME = '';

    /**
     * @var \AMQPQueue|null
     */
    private $queue;

    /**
     * @var int Time in ms
     */
    private $dequeueTimeMs = 0;

    /**
     * @var string
     */
    private $format = QueueDriverInterface::FORMAT_JSON;

    /**
     * @var AMQPChannel|null
     */
    private $channel;

    /**
     * @var \AMQPExchange|null
     */
    private $exchange;

    /**
     * @var string|null
     */
    private $host;

    /**
     * @var string|null
     */
    private $username;

    /**
     * @var string|null
     */
    private $password;

    /**
     * @var array|null
     */
    private $connectionsData;

    /**
     * @var array|null
     */
    private $amqpConnections;

    /**
     * @var PhpAmqpHandler
     */
    private $handler;

    private $port;

    private $vhost;

    public function __construct()
    {
        parent::__construct();
        if (!extension_loaded('pcntl')) {
            throw new AMQPRuntimeException('Unable to process signals. Miss configuration.');
        }
    }

    /**
     * Method responsible to catch the parameters to configure the driver
     *
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $format
     */
    public function configure($host, $username, $password, $format = QueueDriverInterface::FORMAT_JSON, $port = null, $vhost = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->vhost = $vhost;

        $this->connectionsData[] = [
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->username,
            'password' => $this->password,
            'vhost' => $this->vhost,
        ];
        $this->format = $format;
    }

    /**
     * Method responsible to open the connection with the broker
     *
     * @param bool $shuffle - used to randomize the connections if exists more than one
     * @throws \Exception
     */
    public function connect($shuffle = true)
    {
        if (empty($this->connectionsData)) {
            throw new \Exception('No data available to make the connection.');
        }

        try {
            if ($shuffle && count($this->connectionsData) > 1) {
                shuffle($connectionsData);
            }
            $this->addConnection(AMQPStreamConnection::create_connection($this->connectionsData, []));
        } catch (\AMQPConnectionException $connectionException) {
            echo $connectionException->getMessage();
        }
    }

    /**
     * Add a connection to the array of connections
     * @param $connection
     */
    public function addConnection($connection)
    {
        $this->amqpConnections[] = $connection;
    }

    /**
     * Responsible to disconnect with the broker
     * @throws \AMQPException
     * @throws \Exception
     */
    public function disconnect()
    {
        try {
            if ($this->amqpConnections) {
                foreach ($this->amqpConnections as $connection) {
                    if ($connection instanceof AMQPStreamConnection) {
                        $connection->close();
                    }
                }
            }
        } catch (\AMQPConnectionException $exception) {
            echo $exception->getMessage();
        }
    }

    /**
     * Creates the QueueMessage object
     * @return QueueMessage|QueueMessageInterface
     */
    public function createQueueMessage(): QueueMessage
    {
        $msg = new QueueMessage();
        $msg->setContext(new Context());
        return $msg;
    }

    /**
     * Verifies if there is some connection opened with the broker
     * @return bool
     */
    public function isConnected(): bool
    {
        if ($this->amqpConnections) {
            foreach ($this->amqpConnections as $connection) {
                if ($connection->isConnected()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @todo verify if the method is need
     * @return bool
     */
    public function isSubscribed(): bool
    {
        return null !== $this->queue;
    }

    /**
     * @todo verify the need of this function
     * @param string $queue
     */
    public function subscribe($queue)
    {
    }

    /**
     * @todo verify the need of this function
     * @throws \Exception
     */
    public function unSubscribe()
    {
    }

    /**
     * @todo verify the need of this function
     * {@inheritdoc}
     */
    public function ack()
    {
    }

    /**
     * @todo verify the need of this function
     * {@inheritdoc}
     */
    public function nack()
    {
    }

    /**
     * @todo verify the need of this function
     * {@inheritdoc}
     */
    public function doDestroy()
    {
        try {
            $this->disconnect();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Creates a message and prepare the content to publish
     * {@inheritdoc}
     */
    public function send(QueueMessageInterface $queueMessage, $destination = null, $options = []): bool
    {
        if (!$queueMessage->getQueue()) {
            throw new \Exception('Please declare a queue before send some message');
        }

        $messageBody = $this->getSerializer()->serialize($queueMessage, $this->format);
        $messageHeaders = new AMQPTable($queueMessage->getHeaders());
        $message = new AMQPMessage($messageBody);
        $message->set('application_headers', $messageHeaders);
        $this->publish($message, $queueMessage->getQueue());
        return true;
    }

    /**
     * @todo verify the need of this function
     * {@inheritdoc}
     */
    public function receive()
    {
    }

    /**
     * @todo verify the need of this function
     * Returns One Serializable object from the queue.
     * It requires to subscribe previously to a specific queue
     *
     * @throws \Exception
     */
    public function receiveNoWait()
    {
    }

    /**
     * @todo verify the need of this function
     * @return int The time it took in ms to de-queue and deserialize the message
     */
    public function getDequeueingTimeMs()
    {
        return $this->dequeueTimeMs;
    }

    /**
     * @todo verify if this method is need
     * {@inheritdoc}
     */
    public function purge(string $queue)
    {
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param int|float $seconds
     */
    public function sleep($seconds)
    {
        if ($seconds < 1) {
            usleep((int) $seconds * 1000000);
        } else {
            sleep((int) $seconds);
        }
    }

    /**
     * Declares the queue to drive the message
     *
     * @param string $name The name of the que
     * @param int $flag See AMQPQueue::setFlags()
     * @param array $arguments See AMQPQueue::setArguments()
     *
     * @return array|null
     *
     * @throws \AMQPChannelException
     */
    public function declareQueue(string $name, int $flag = AMQP_DURABLE, array $arguments = [])
    {
        if (!$this->channel) {
            throw new \AMQPChannelException('There is no channel available');
        }

        $this->queue = $name;
        $durable = $flag === AMQP_DURABLE ? true : false;
        return $this->channel->queue_declare($this->queue, false, $durable, false, false, false, new AMQPTable([$arguments]));
    }

    /**
     * Catch a channel inside the connection
     * @return AMQPChannel
     */
    public function declareChannel(): AMQPChannel
    {
        $this->channel = $this->amqpConnections[0]->channel();
        $this->channel->basic_qos(null, self::PREFETCH_COUNT, null);
        return $this->channel;
    }

    /**
     * AMQP Exchange is the publishing mechanism. This method declares a new default exchange
     * @param $exchange
     */
    public function declareExchange($exchange)
    {
        $this->exchange = $exchange;
        $this->exchange->setName(self::EXCHANGE_NAME);
        $this->channel->exchange_declare($this->exchange->getName(), AMQPExchangeType::DIRECT, false, true, false);
        $this->channel->queue_bind($this->queue, $this->exchange->getName(), $this->queue);
    }

    /**
     * Publish a message
     * @param AMQPMessage $message
     * @param string $queueName
     * @param array $options
     */
    public function publish(AMQPMessage $message, string $queueName, array $options = [])
    {
        try {
            $this->declareChannel();
            $this->declareQueue($queueName, AMQP_DURABLE, $options);
            $this->channel->basic_publish($message, self::EXCHANGE_NAME, $queueName);
        } catch (\Exception $exception) {
            $this->getExceptionHandler()($exception, ['headers' => $message->getHeaders(), 'body' => $message->getBody()]);
        }
    }

    /**
     * Returns the format used on serialize function
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Start the consume flow
     * @param EndpointInterface $endpoint
     * @return bool
     * @throws \Exception
     */
    public function consume(EndpointInterface $endpoint): bool
    {
        try {
            $this->handler = new PhpAmqpHandler($endpoint, (int) $this->expirationCount, $this->format, $this->serializer);
            $this->handler->setExceptionHandler($this->getExceptionHandler());

            if ($this->smartesbHelper) {
                $this->handler->setSmartesbHelper($this->smartesbHelper);
            }
            if ($this->logger) {
                $this->handler->setLogger($this->logger);
            }

            $channel = $this->declareChannel();
            if ($channel instanceof AMQPChannel) {
                $this->handler->setChannel($channel);
            }
            $queueName = $this->getQueueName($endpoint);
            $this->declareQueue($queueName);
            $this->handler->consume(self::CONSUMER_TAG, $channel, $queueName);
        } catch (\AMQPException $exception) {
            echo $exception->getMessage();
        } finally {
            $this->disconnect();
        }

        return true;
    }

    /**
     * Returns the queue name properly treated with queue prefix
     * @param EndpointInterface $endpoint
     * @return string
     */
    protected function getQueueName(EndpointInterface $endpoint): string
    {
        $options = $endpoint->getOptions();

        return "{$options[QueueProtocol::OPTION_PREFIX]}{$options[QueueProtocol::OPTION_QUEUE_NAME]}";
    }

    /**
     * Stop the consumer not gracefully closing the connection
     * @throws \AMQPException
     */
    public function stopHard()
    {
        echo 'Stopping consumer by closing connection.' . PHP_EOL;
        $this->disconnect();
    }

    /**
     * Tell the server you are going to stop consuming
     * It will finish up the last message and not send you any more
     * @throws \Exception
     */
    public function stop()
    {
        echo 'Stopping consumer by cancel command.' . PHP_EOL;
        if ($this->isConnected()) {
            $this->handler->stopConsume(self::CONSUMER_TAG);
        }
    }

}