<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Exception\AMQPProtocolException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class PhpAmqpLibDriver.
 */
class PhpAmqpLibDriver extends Service implements AsyncQueueDriverInterface
{
    /**
     * This field specifies the prefetch window size in octets.
     * The server will send a message in advance if it is equal to or smaller in size than the available prefetch size
     * (and also falls into other prefetch limits).
     * May be set to zero, meaning "no specific limit", although other prefetch limits may still apply.
     * The prefetch-size is ignored if the no-ack option is set.
     * This parameter is not implemented in RabbitMQ as checked in:
     * https://www.rabbitmq.com/specification.html#method-status-basic.qos.
     */
    const PREFETCH_SIZE = null;

    /**
     * Represents the amount of message to consume by iteration.
     */
    const PREFETCH_COUNT = 10;

    /*
     * Default exchange name
     */
    const EXCHANGE_NAME = '';

    /**
     * Default port to connection.
     */
    const DEFAULT_PORT = 5672;

    /**
     * Default host to connect.
     */
    const DEFAULT_HOST = 'localhost';

    /**
     * Default value to connection timeout
     */
    const CONNECTION_TIMEOUT = 3.0;

    /**
     * Default value to read timeout
     */
    const READ_TIMEOUT = 10;

    /**
     * Default value to heartbeat
     */
    const HEARTBEAT = 60;

    /**
     * @var AMQPChannel|null
     */
    private $channel;

    /**
     * @var array|null
     */
    protected $connectionsData;

    /**
     * @var AMQPStreamConnection
     */
    protected $stream;

    /**
     * @var int
     */
    protected $prefetchCount;

    /**
     * @var double
     */
    protected $connectionTimeout;

    /**
     * @var double
     */
    protected $readTimeout;

    /**
     * @var int
     */
    protected $heartbeat;

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    public function configure(string $host, string $username, string $password, string $vhost = null)
    {
        $urls = explode(',', $host);

        foreach ($urls as $url) {
            $parsedUrl = parse_url($url);

            $this->connectionsData[] = [
                'host' => $parsedUrl['host'] ?? self::DEFAULT_HOST,
                'user' => $username,
                'password' => $password,
                'vhost' => $vhost,
                'port' => $parsedUrl['port'] ?? self::DEFAULT_PORT,
            ];
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws AMQPProtocolException
     */
    public function connect()
    {
        if (!$this->validateConnection()) {
            try {
                shuffle($this->connectionsData);
                $this->stream = AMQPStreamConnection::create_connection($this->connectionsData, [
                    'read_write_timeout' => $this->readTimeout,
                    'connection_timeout' => $this->connectionTimeout,
                    'heartbeat' => $this->heartbeat
                ]);
            } catch (AMQPIOException $exception) {
                throw new AMQPProtocolException($exception->getCode(), $exception->getMessage(), null);
            }
        } elseif (!$this->isConnected()) {
            $this->stream->reconnect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->validateConnection()) {
            $this->stream->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createQueueMessage(): QueueMessageInterface
    {
        $msg = new QueueMessage();
        $msg->setContext(new Context());

        return $msg;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool
    {
        return $this->validateConnection() && $this->stream->isConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(string $consumerTag)
    {
        $this->channel->basic_cancel($consumerTag);
        $this->channel = null;
        $this->disconnect();
    }

    /**
     * Set the value for prefetch_count option case it comes form configuration. Otherwise, takes the default value.
     *
     * @param int $prefetchCount
     */
    public function setPrefetchCount(int $prefetchCount)
    {
        $this->prefetchCount = $prefetchCount;
    }

    /**
     * @param float $connectionTimeout
     */
    public function setConnectionTimeout(float $connectionTimeout)
    {
        $this->connectionTimeout = $connectionTimeout;
    }

    /**
     * @param float $readTimeout
     */
    public function setReadTimeout(float $readTimeout)
    {
        $this->readTimeout = $readTimeout;
    }

    /**
     * @param int $heartbeat
     */
    public function setHeartbeat(int $heartbeat)
    {
        $this->heartbeat = $heartbeat;
    }

    /**
     * {@inheritdoc}
     */
    public function consume(string $consumerTag, string $queueName, callable $callback = null)
    {
        $this->declareChannel();
        $this->declareQueue($queueName);
        $this->channel->basic_consume($queueName, $consumerTag, false, false, false, false, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function ack(QueueMessageInterface $message = null)
    {
        $this->channel->basic_ack($message->getMessageId());
    }

    /**
     * {@inheritdoc}
     */
    public function nack(QueueMessageInterface $message = null)
    {
        $this->channel->basic_nack($message->getMessageId());
    }

    /**
     * {@inheritdoc}
     */
    public function send(string $destination, string $body = '', array $headers = []): bool
    {
        $this->declareChannel();
        $this->declareQueue(
            $destination,
            QueueMessage::DELIVERY_MODE_PERSISTENT,
            array_filter($headers, function ($value) {
                return 0 === strpos($value, 'x-');
            }, ARRAY_FILTER_USE_KEY));

        /*
         * Headers are duplicated as "application_headers" too pass any other header coming from the MessageInterface
         * that might not be compatible/relevant to AMQP headers.
         */
        $properties = $headers;
        $properties['application_headers'] = new AMQPTable($headers);

        $amqpMessage = new AMQPMessage($body, $properties);

        $this->channel->basic_publish($amqpMessage, self::EXCHANGE_NAME, $destination);

        return true;
    }

    /**
     * Declares the queue to drive the message.
     *
     * @param string $queueName The name of the queue
     * @param int    $durable
     * @param array  $arguments See AMQPQueue::setArguments()
     *
     * @return array|null
     */
    public function declareQueue(string $queueName, int $durable = QueueMessage::DELIVERY_MODE_PERSISTENT, array $arguments = [])
    {
        return $this->channel->queue_declare($queueName, false, $durable, false, false, false, new AMQPTable($arguments));
    }

    /**
     * Catch a channel inside the connection.
     *
     * @return AMQPChannel
     */
    public function declareChannel(): AMQPChannel
    {
        if (!$this->channel instanceof AMQPChannel || !$this->channel->is_open()) {
            $this->channel = $this->stream->channel();
            $this->channel->basic_qos(self::PREFETCH_SIZE, $this->prefetchCount, null);
        }

        return $this->channel;
    }

    /**
     * {@inheritdoc}
     */
    public function waitNoBlock()
    {
        $this->channel->wait(null, true);
    }

    /**
     * {@inheritdoc}
     */
    public function wait()
    {
        $this->channel->wait();
    }

    /**
     * Validate if there is some connection available.
     */
    protected function validateConnection()
    {
        return $this->stream instanceof AbstractConnection;
    }
}
