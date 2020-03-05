<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPIOException;
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
     * @var int Time in ms
     */
    private $dequeueTimeMs = 0;

    /**
     * @var string
     */
    protected $format = AsyncQueueDriverInterface::FORMAT_JSON;

    /**
     * @var AMQPChannel|null
     */
    private $channel;

    /**
     * @var \AMQPExchange|null
     */
    private $exchange;

    /**
     * @var array|null
     */
    protected $connectionsData;

    /**
     * @var array
     */
    protected $amqpConnections = [];

    /**
     * Number of the port used by the broker
     *
     * @var int $port
     */
    protected $port;

    /**
     * Method responsible to catch the parameters to configure the driver.
     *
     * {@inheritDoc}
     */
    public function configure(string $host, string $username, string $password, string $vhost = null)
    {
        $this->connectionsData[] = [
            'host' => $host,
            'user' => $username,
            'password' => $password,
            'vhost' => $vhost,
            'port' => $this->port
        ];
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
        if ($shuffle) {
            $this->shuffleConnections();
        }

        try {
            $this->addConnection(AMQPStreamConnection::create_connection($this->connectionsData, [
                'insist' => true,
                'login_method' => 'AMQPLAIN',
                'locale' => 'en_UK',
                'connection_timeout' => 60.0,
                'read_write_timeout' => 50.0,
                'context' => null,
                'keepalive' => true,
                'heartbeat' => 0,
            ]));
        } catch (AMQPIOException $exception) {
            throw new \AMQPConnectionException($exception->getMessage());
        }
    }

    /**
     * Add a connection to the array of connections.
     *
     * @param $connection
     */
    public function addConnection(AbstractConnection $connection)
    {
        $this->amqpConnections[] = $connection;
    }

    /**
     * Responsible to disconnect with the broker.
     *
     * @throws \Exception
     */
    public function disconnect()
    {
        foreach ($this->amqpConnections as $connection) {
            if ($connection instanceof AbstractConnection) {
                $connection->close();
            }
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
     */
    public function isConnected(): bool
    {
        foreach ($this->amqpConnections as $connection) {
            if ($connection->is_connected()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy()
    {
        $this->amqpConnections = [];
        $this->connectionsData = [];
        $this->disconnect();
    }

    /**
     * Consumes the message and dispatch it to others features.
     *
     * @param string $consumerTag
     * @param string $queueName
     * @param callable|null $callback
     * @return string $consumerTag
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
    public function ack(int $messageId = null)
    {
        $this->channel->basic_ack($messageId);
    }

    /**
     * {@inheritdoc}
     */
    public function nack(int $messageId = null)
    {
        $this->channel->basic_nack($messageId);
    }

    /**
     * Creates a message and prepare the content to publish.
     *
     * {@inheritdoc}
     * @throws \Exception
     */
    public function send(QueueMessageInterface $message, $destination = null, array $arguments = [])
    {
        $this->declareChannel();
        $this->declareQueue($message->getQueue(), AMQP_DURABLE, $arguments);
        $messageBody = $this->getSerializer()->serialize($message, $this->format);
        $messageHeaders = new AMQPTable($message->getHeaders());
        $amqpMessage = new AMQPMessage($messageBody);
        $amqpMessage->set('application_headers', $messageHeaders);
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
     *
     */
    public function declareQueue(string $queueName, int $durable = AMQP_DURABLE, array $arguments = [])
    {
        return $this->channel->queue_declare($queueName, false, $durable, false, false, false, new AMQPTable([$arguments]));
    }

    /**
     * Catch a channel inside the connection.
     */
    public function declareChannel(int $prefetchSize = self::PREFETCH_SIZE, int $prefetchCount = self::PREFETCH_COUNT): AMQPChannel
    {
        $this->validateConnection();
        $this->channel = $this->amqpConnections[0]->channel();
        $this->channel->basic_qos($prefetchSize, $prefetchCount, null);

        return $this->channel;
    }

    /**
     * Returns the format used on serialize function.
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat(string $format = null)
    {
        $this->format = $format;
    }

    /**
     * Shuffles the connection in case there is more than one available.
     */
    protected function shuffleConnections()
    {
        shuffle($this->connectionsData);
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

    public function isConsuming()
    {
        return $this->channel->is_consuming();
    }

    /**
     * @param int $port
     */
    public function setPort(int $port)
    {
        $this->port = $port;
    }

    /**
     * Rreturn one or more connections available on the driver
     *
     * @return array
     */
    public function getAvailableConnections()
    {
        return $this->amqpConnections;
    }

    /**
     * validate if there is some connection available
     *
     * @return void
     * @throws \Exception
     */
    public function validateConnection()
    {
        if (!isset($this->amqpConnections[0]) || !$this->amqpConnections[0] instanceof AbstractConnection) {
            throw new \Exception('No connection available');
        }
    }

}