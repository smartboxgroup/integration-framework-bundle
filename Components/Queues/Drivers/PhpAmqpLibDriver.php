<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerAwareTrait;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class PhpAmqpLibDriver.
 */
class PhpAmqpLibDriver extends Service implements AsyncQueueDriverInterface
{
    use UsesSerializer;
    use UsesExceptionHandlerTrait;

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
    const PREFETCH_COUNT = 1;

    /*
     * To use the default exchange pass an empty string
     */
    const EXCHANGE_NAME = '';

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
     * @var array|null
     */
    private $connectionsData;

    /**
     * @var array|null
     */
    private $amqpConnections;

    /**
     * Number of the port used by the broker
     *
     * @var int $port
     */
    private $port;

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
        try {
            $this->validateConnectionData();
            $this->shuffleConnections($shuffle);
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
        } catch (\Exception $exception) {
            $this->getExceptionHandler()($exception, [$exception->getCode(), $exception->getMessage()]);
        }
    }

    /**
     * Add a connection to the array of connections.
     *
     * @param $connection
     */
    public function addConnection($connection)
    {
        $this->amqpConnections[] = $connection;
    }

    /**
     * Responsible to disconnect with the broker.
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
        } catch (\Exception $exception) {
            $this->getExceptionHandler()($exception, [$exception->getCode(), $exception->getMessage()]);
        }
    }

    /**
     * Creates the QueueMessage object.
     *
     * @return QueueMessage|QueueMessageInterface
     */
    public function createQueueMessage(): QueueMessage
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
     * {@inheritdoc}
     */
    public function destroy()
    {
        try {
            $this->disconnect();
        } catch (\Exception $exception) {
            $this->getExceptionHandler()($exception, [$exception->getCode(), $exception->getMessage()]);
        }
    }

    /**
     * Consumes the message and dispatch it to others features.
     *
     * @param AMQPChannel $channel
     */
    public function consume(string $consumerTag, string $queueName, callable $callback = null)
    {
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
     *
     * @throws \Exception
     */
    public function send(QueueMessageInterface $message, $destination = null, array $arguments = [])
    {
        $this->declareChannel();
        $this->validateQueueExists($message);
        $messageBody = $this->getSerializer()->serialize($message, $this->format);
        $messageHeaders = new AMQPTable($message->getHeaders());
        $amqpMessage = new AMQPMessage($messageBody);
        $amqpMessage->set('application_headers', $messageHeaders);
        $this->declareQueue($message->getQueue(), AMQP_DURABLE, $arguments);
        $this->channel->basic_publish($amqpMessage, self::EXCHANGE_NAME, $message->getQueue());
        return true;
    }

    /**
     * @return int The time it took in ms to de-queue and deserialize the message
     */
    public function getDequeueingTimeMs()
    {
        return $this->dequeueTimeMs;
    }

    /**
     * Declares the queue to drive the message.
     *
     * @param string $name      The name of the queue
     * @param array  $arguments See AMQPQueue::setArguments()
     *
     * @return array|null
     *
     * @throws \AMQPChannelException
     */
    public function declareQueue(string $queueName, int $durable = AMQP_DURABLE, array $arguments = [])
    {
        return $this->channel->queue_declare($queueName, false, $durable, false, false, false, new AMQPTable([$arguments]));
    }

    /**
     * Catch a channel inside the connection.
     */
    public function declareChannel(): AMQPChannel
    {
        $this->channel = $this->amqpConnections[0]->channel();
        $this->channel->basic_qos(self::PREFETCH_SIZE, self::PREFETCH_COUNT, null);

        return $this->channel;
    }

    /**
     * AMQP Exchange is the publishing mechanism. This method declares a new default exchange.
     */
    public function declareExchange(string $queueName)
    {
        $this->channel->exchange_declare(self::EXCHANGE_NAME, AMQPExchangeType::DIRECT, false, true, false);
        $this->channel->queue_bind($queueName, self::EXCHANGE_NAME, $queueName);
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
    public function setFormat(string $format = QueueDriverInterface::FORMAT_JSON)
    {
        $this->format = $format;
    }

    /**
     * Validate if the information related to the connection is available.
     *
     * @throws \Exception
     */
    private function validateConnectionData()
    {
        if (empty($this->connectionsData)) {
            throw new \Exception('No data available to make the connection.');
        }
    }

    /**
     * Shuffles the connection in case there is more than one available.
     *
     * @param bool $isShuffle
     */
    private function shuffleConnections($isShuffle = true)
    {
        if ($isShuffle && count($this->connectionsData) > 1) {
            shuffle($this->connectionsData);
        }
    }

    /**
     * Validate if a queue already was declared to this connection.
     *
     * @param $queueMessage
     *
     * @throws \Exception
     */
    private function validateQueueExists($queueMessage)
    {
        if (!$queueMessage->getQueue()) {
            throw new \Exception('Please declare a queue before send some message');
        }
    }

    /**
     * Validate if a channel was already declared on the driver.
     *
     * @throws \AMQPChannelException
     */
    private function validateChannel()
    {
        if (!$this->channel) {
            throw new \AMQPChannelException('There is no channel available');
        }
    }

    /**
     */
    public function waitNoBlock()
    {
        $this->channel->wait(null, true);
    }
    /**
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
}
