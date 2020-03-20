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
        try {
            $this->disconnect();
        } catch (\Exception $e) {
            throw $e;
        }
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
     * Method responsible to open the connection with the broker.
     *
     * @param bool $shuffle - used to randomize the connections if exists more than one
     *
     * @throws \Exception
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
     * Responsible to disconnect with the broker.
     *
     * @throws \Exception
     * @return void
     */
    public function disconnect()
    {
        if ($this->validateConnection()) {
            $this->stream->close();
        }
    }

    /**
     * Creates the QueueMessage object.
     *
     * @return QueueMessageInterface
     */
    public function createQueueMessage(): QueueMessageInterface
    {
        $msg = new QueueMessage();
        $msg->setContext(new Context());

        return $msg;
    }

    /**
     * Verifies if there is some connection opened with the broker.
     *
     * @return bool
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
    public function send(QueueMessageInterface $message, $destination = null, array $arguments = []): bool
    {
        $this->declareChannel();
        $this->declareQueue($message->getQueue(), QueueMessage::DELIVERY_MODE_PERSISTENT, $arguments);

        $messageBody = $this->getSerializer()->serialize($message, $this->format);
        $amqpMessage = new AMQPMessage($messageBody);

        $this->setAMQPMessageHeaders($amqpMessage, new AMQPTable($message->getHeaders()));

        $this->channel->basic_publish($amqpMessage, self::EXCHANGE_NAME, $message->getQueue());

        return true;
    }

    /**
     * Sets all the headers passed as an AMQPTable as the AMQPMessage headers.
     *
     * @param AMQPMessage $message
     * @param AMQPTable $headers
     */
    protected function setAMQPMessageHeaders(AMQPMessage $message, AMQPTable $headers)
    {
        // Pass all the available headers as "application_headers" in case they cannot be matched as an AMQPMessage header
        $message->set('application_headers', $headers);

        foreach ($headers as $header => $value) {
            try {
                //In AMQPTables, $value[0] is the type and $value[1] is the content.
                $message->set($header, $value[1]);
            } catch (\OutOfBoundsException $e) {
                // No valid AMQP header matched, ignore.
            }
        }
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
        if (!$this->channel instanceof AMQPChannel) {
            $this->channel = $this->stream->channel();
            $this->channel->basic_qos($prefetchSize, $prefetchCount, null);
        }

        return $this->channel;
    }

    /**
     * Returns the format used on serialize function.
     *
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Set the format used by serialize/deserialize functions
     *
     * @param string $format
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
     * Checks if any messages are being consumed
     *
     * @return bool
     */
    public function isConsuming(): bool
    {
        return $this->channel->is_consuming();
    }

    /**
     * Validate if there is some connection available
     */
    protected function validateConnection()
    {
        return $this->stream instanceof AbstractConnection;
    }
}
