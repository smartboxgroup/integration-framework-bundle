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
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class PhpAmqpLibDriver.
 */
class PhpAmqpLibDriver extends Service implements AsyncQueueDriverInterface
{
    use UsesSerializer;

    /**
     * This field specifies the prefetch window size in octets.
     * The server will send a message in advance if it is equal to or smaller in size than the available prefetch size
     * (and also falls into other prefetch limits).
     * May be set to zero, meaning "no specific limit", although other prefetch limits may still apply.
     * The prefetch-size is ignored if the no-ack option is set.
     */
    const PREFETCH_SIZE = null;

    /**
     * Represents the amount of message to consume by iteration
     */
    const PREFETCH_COUNT = 10;

    /*
     * Default exchange name
     */
    const EXCHANGE_NAME = '';

    /**
     * Default port to connection
     */
    const DEFAULT_PORT = 5672;

    /**
     * Default host to connect
     */
    const DEFAULT_HOST = 'localhost';

    /**
     * @var string
     */
    protected $format = AsyncQueueDriverInterface::FORMAT_JSON;

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
     * @throws \Exception
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     * @throws \Exception
     * @throws AMQPProtocolException
     */
    public function connect($shuffle = true)
    {
        $this->shuffleConnections($shuffle);
        try {
            $this->stream = AMQPStreamConnection::create_connection($this->connectionsData, []);
        } catch (AMQPIOException $exception) {
            throw new AMQPProtocolException($exception->getCode(), $exception->getMessage(), null);
        }
    }

    /**
     * {@inheritDoc}
     * @throws \Exception
     * @throws \Exception
     */
    public function disconnect()
    {
        if ($this->validateConnection()) {
            $this->stream->close();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createQueueMessage(): QueueMessageInterface
    {
        $msg = new QueueMessage();
        $msg->setContext(new Context());

        return $msg;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritdoc}
     * @throws \Exception
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
     * @throws \Exception
     */
    public function send(QueueMessageInterface $message, $destination = null, array $arguments = []): bool
    {
        $this->declareChannel();
        $this->declareQueue($message->getQueue(), QueueMessage::DELIVERY_MODE_PERSISTENT, $arguments);

        /*
         * Headers are duplicated as "application_headers" too pass any other header coming from the MessageInterface
         * that might not be compatible/relevant to AMQP headers.
         */
        $properties = $message->getHeaders();
        $properties['application_headers'] = new AMQPTable($properties);

        $messageBody = $this->getSerializer()->serialize($message, $this->format);
        $amqpMessage = new AMQPMessage($messageBody, $properties);

        $this->channel->basic_publish($amqpMessage, self::EXCHANGE_NAME, $message->getQueue());

        return true;
    }

    /**
     * Declares the queue to drive the message.
     *
     * @param string $queueName The name of the queue
     * @param int $durable
     * @param array $arguments See AMQPQueue::setArguments()
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
     * @param int $prefetchSize
     * @param int $prefetchCount
     * @return AMQPChannel
     * @throws \Exception
     */
    public function declareChannel(int $prefetchSize = self::PREFETCH_SIZE, int $prefetchCount = self::PREFETCH_COUNT): AMQPChannel
    {
        if (!$this->channel instanceof AMQPChannel || !$this->channel->is_open()) {
            $this->channel = $this->stream->channel();
            $this->channel->basic_qos($prefetchSize, $prefetchCount, null);
        }

        return $this->channel;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormat(string $format = null)
    {
        $this->format = $format;
    }

    /**
     * Shuffles the connection in case there is more than one available.
     *
     * @param bool $shuffle
     */
    protected function shuffleConnections(bool $shuffle = true)
    {
        if ($shuffle) {
            shuffle($this->connectionsData);
        }
    }

    /**
     * {@inheritdoc}
     * @throws \ErrorException
     */
    public function waitNoBlock()
    {
        $this->channel->wait(null, true);
    }

    /**
     * {@inheritdoc}
     * @throws \ErrorException
     */
    public function wait()
    {
        $this->channel->wait();
    }

    /**
     * Validate if there is some connection available
     */
    protected function validateConnection()
    {
        return $this->stream instanceof AbstractConnection;
    }
}
