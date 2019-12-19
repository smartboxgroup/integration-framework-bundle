<?php


namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\PhpAmqpLibQueueManager;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueManager;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use Smartbox\Integration\FrameworkBundle\Service;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class PhpAmqpLibDriver
 * @package Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers
 */
class PhpAmqpLibDriver extends Service implements PurgeableQueueDriverInterface
{
    use UsesSerializer;
    use UsesExceptionHandlerTrait;
    use ContainerAwareTrait;
    
    /**
     * Maximum amount of time (in seconds) to wait for a message.
     */
    const WAIT_TIMEOUT = 10;

    /**
     * Maximum amount of time (in seconds) to set as an interval while consuming messages
     */
    const WAIT_PERIOD = 0.2;

    /**
     * @var \AMQPQueue
     */
    private $queue;

    /**
     * @var int Time in ms
     */
    private $dequeueingTimeMs = 0;

    /**
     * @var string
     */
    private $format;

    /**
     * @var PhpAmqpLibQueueManager
     */
    private $manager;

    /**
     * @var string Host form Message Broker
     */
    private $host;

    /**
     * @var string Username to connect to Message broker
     */
    private $username;

    /**
     * @var string $password
     */
    private $password;

    /**
     * @var string $port
     */
    private $port;

    /**
     * @var string $vhost
     */
    private $vhost;

    /**
     * @var array
     */
    private $connectionId = [];

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * PhpAmqpLibDriver constructor.
     * @param QueueManager $manager
     */
    public function __construct(PhpAmqpLibQueueManager $manager)
    {
        parent::__construct();
        $this->manager = $manager;
        $this->format = self::FORMAT_JSON;
    }

    /**
     * PhpAmqpLibDriver destruct
     */
    public function __destruct()
    {
        $this->manager->disconnect();
    }

    /**
     * @param $host
     * @param string $username
     * @param string $password
     * @param string $format
     * @param null $port
     * @param null $vhost
     */
    public function configure($host, $username, $password, $format = self::FORMAT_JSON, $port = null, $vhost = null)
    {
        $this->format = $format;
    }

    /**
     * @throws \AMQPException
     */
    public function connect()
    {
        $this->manager->connect();
    }

    /**
     * @throws \Exception
     */
    public function disconnect()
    {
        $this->manager->disconnect();
    }

    /**
     * @return QueueMessage|QueueMessageInterface
     */
    public function createQueueMessage($queueName = null, $options = [])
    {
        $msg = new QueueMessage();
        $msg->setContext(new Context());
        $this->setChannel();
        $this->manager->declareQueue($queueName, AMQP_DURABLE, $options);
        return $msg;
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return $this->manager->isConnected();
    }

    /**
     * @return bool
     */
    public function isSubscribed()
    {
        return null !== $this->queue;
    }

    /**
     * @param string $queue
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function subscribe($queue)
    {
        if ($this->currentEnvelope) {
            throw new \RuntimeException('Don\'t be greedy and process the message you already have!');
        }

        $this->queue = $this->manager->getQueue((string) $queue);
    }

    /**
     * @throws \Exception
     */
    public function unSubscribe()
    {
        $this->disconnect();
        $this->queue = $this->currentEnvelope = null;
    }

    /**
     * {@inheritdoc}
     */
    public function ack()
    {
        if (!$this->currentEnvelope) {
            throw new \RuntimeException('You must first receive a message, before acking it');
        }

        $this->queue->ack($this->currentEnvelope->getDeliveryTag());
        $this->currentEnvelope = null;
    }

    /**
     * {@inheritdoc}
     */
    public function nack()
    {
        if (!$this->currentEnvelope) {
            throw new \RuntimeException('You must first receive a message, before nacking it');
        }

        $this->queue->nack($this->currentEnvelope->getDeliveryTag(), AMQP_REQUEUE);
        $this->currentEnvelope = null;
    }

    /**
     * {@inheritdoc}
     */
    public function doDestroy()
    {
        $this->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    public function send(QueueMessageInterface $message, $destination = null)
    {
        $messageBody = $this->getSerializer()->serialize($message, $this->format);
        $messageHeaders = new AMQPTable($message->getHeaders());
        $message = new AMQPMessage($messageBody);
        $message->set('application_headers', $messageHeaders);
        $this->manager->publish($message);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function receive()
    {
        if ($this->currentEnvelope) {
            throw new \LogicException('AmqpQueueDriver: You have to subscribe before receiving.');
        }
        $waited = 0;

        while (null === ($msg = $this->receiveNoWait()) && static::WAIT_TIMEOUT > $waited) {
            $this->sleep(static::WAIT_PERIOD);
            $waited += static::WAIT_PERIOD;
        }

        return $msg;
    }

    /**
     * Returns One Serializable object from the queue.
     *
     * It requires to subscribe previously to a specific queue
     *
     * @throws \Exception
     *
     * @return \Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface|null
     */
    public function receiveNoWait()
    {
        if ($this->currentEnvelope) {
            throw new \RuntimeException(
                'AmqpQueueDriver: This driver has a message that was not acknowledged yet. A message must be processed and acknowledged before receiving new messages.'
            );
        }

        $this->dequeueingTimeMs = 0;

        $this->currentEnvelope = $this->queue->get();

        $msg = null;

        if (!$this->currentEnvelope) {
            return null;
        }

        $start = microtime(true);
        $deserializationContext = new DeserializationContext();

        try{
            /** @var QueueMessageInterface $msg */
            $msg = $this->getSerializer()->deserialize($this->currentEnvelope->getBody(), SerializableInterface::class, $this->format, $deserializationContext);
        } catch (\Exception $exception) {
            $this->getExceptionHandler()($exception, ['headers' => $this->currentEnvelope->getHeaders(), 'body' => $this->currentEnvelope->getBody()]);
            $this->ack();
            return null;
        }
        foreach ($this->currentEnvelope->getHeaders() as $header => $value) {
            $msg->setHeader($header, $value);
        }

        // Calculate how long it took to deserilize the message
        $this->dequeueingTimeMs = (int) ((microtime(true) - $start) * 1000);

        return $msg;
    }

    /**
     * @return int The time it took in ms to de-queue and deserialize the message
     */
    public function getDequeueingTimeMs()
    {
        return $this->dequeueingTimeMs;
    }

    /**
     * {@inheritdoc}
     */
    public function purge(string $queue)
    {
        $this->manager->getQueue($queue)->purge();
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
     *
     */
    private function fillByContainer()
    {
        $this->container->get();
    }

    /**
     * @return PhpAmqpLibQueueManager|QueueManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param string $queueName
     * @param int $flag
     * @param array $options
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function setQueue($queueName)
    {
        $this->manager->declareQueue($queueName);
    }

    /**
     * @param $exchange
     */
    public function setExchange($exchange)
    {
        $this->manager->declareExchange($exchange);
    }

    /**
     *
     */
    public function setChannel()
    {
        $this->manager->declareChannel();
    }
}