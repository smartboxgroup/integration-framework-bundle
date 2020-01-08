<?php


namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\PhpAmqpLibQueueManager;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class PhpAmqpLibDriver
 * @package Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers
 */
class PhpAmqpLibDriver extends Service implements PurgeableQueueDriverInterface
{
    use UsesSerializer;
    use UsesExceptionHandlerTrait;

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
     * @var AMQPChannel
     */
    private $channel;

    /**
     * PhpAmqpLibDriver constructor.
     * @param QueueManager $manager
     */
    public function __construct(PhpAmqpLibQueueManager $manager, string $format = null)
    {
        parent::__construct();
        $this->manager = $manager;
        $this->format = $format ?? self::FORMAT_JSON;
    }

    /**
     * PhpAmqpLibDriver destruct
     */
    final function __destruct()
    {
        try {
            $this->manager->disconnect();
        } catch (\Exception $connectionException) {
            $this->getExceptionHandler()($connectionException);
        }
    }

    /**
     * @param $host
     * @param string $username
     * @param string $password
     * @param string $format
     */
    public function configure($host, $username, $password, $format = self::FORMAT_JSON)
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
     * @throws \AMQPException
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
     * @todo verify the need of this function
     * @param string $queue
     */
    public function subscribe($queue)
    {
        /*if ($this->currentEnvelope) {
            throw new \RuntimeException('Don\'t be greedy and process the message you already have!');
        }
        $this->queue = $this->manager->getQueue((string) $queue);*/
    }

    /**
     * @todo verify the need of this function
     * @throws \Exception
     */
    public function unSubscribe()
    {
        /*$this->disconnect();
        $this->queue = $this->currentEnvelope = null;*/
    }

    /**
     * @todo verify the need of this function
     * {@inheritdoc}
     */
    public function ack()
    {
        /*if (!$this->currentEnvelope) {
            throw new \RuntimeException('You must first receive a message, before acking it');
        }

        $this->queue->ack($this->currentEnvelope->getDeliveryTag());
        $this->currentEnvelope = null;*/
    }

    /**
     * @todo verify the need of this function
     * {@inheritdoc}
     */
    public function nack()
    {
        /*if (!$this->currentEnvelope) {
            throw new \RuntimeException('You must first receive a message, before nacking it');
        }

        $this->queue->nack($this->currentEnvelope->getDeliveryTag(), AMQP_REQUEUE);
        $this->currentEnvelope = null;*/
    }

    /**
     * @todo verify the need of this function
     * {@inheritdoc}
     */
    public function doDestroy()
    {
        try {
            $this->manager->disconnect();
        } catch (\Exception $e) {
            $this->getExceptionHandler()($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function send(QueueMessageInterface $queueMessage, $destination = null, $options = [])
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
        /*if ($this->currentEnvelope) {
            throw new \LogicException('AmqpQueueDriver: You have to subscribe before receiving.');
        }
        $waited = 0;

        while (null === ($msg = $this->receiveNoWait()) && static::WAIT_TIMEOUT > $waited) {
            $this->sleep(static::WAIT_PERIOD);
            $waited += static::WAIT_PERIOD;
        }

        return $msg;*/
    }

    /**
     * @todo verify the need of this function
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
        /*if ($this->currentEnvelope) {
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
//            $msg = $this->getSerializer()->deserialize($this->currentEnvelope->getBody(), SerializableInterface::class, $this->format, $deserializationContext);
        /*} catch (\Exception $exception) {
            $this->getExceptionHandler()($exception, ['headers' => $this->currentEnvelope->getHeaders(), 'body' => $this->currentEnvelope->getBody()]);
            $this->ack();
            return null;
        }
        foreach ($this->currentEnvelope->getHeaders() as $header => $value) {
            $msg->setHeader($header, $value);
        }

        // Calculate how long it took to deserilize the message
        $this->dequeueingTimeMs = (int) ((microtime(true) - $start) * 1000);

        return $msg;*/
    }

    /**
     * @todo verify the need of this function
     * @return int The time it took in ms to de-queue and deserialize the message
     */
    public function getDequeueingTimeMs()
    {
//        return $this->dequeueingTimeMs;
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
     * @return PhpAmqpLibQueueManager|QueueManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param string $queueName
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function setQueue($queueName)
    {
        $this->declareQueue($queueName);
    }

    /**
     * @param $exchange
     */
    public function setExchange($exchange)
    {
        $this->declareExchange($exchange);
    }

    /**
     * Declares a channel to connect with the broker
     */
    public function setChannel()
    {
        $this->declareChannel();
    }

    /**
     * Declares the queue to drive the message
     *
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
        return $this->channel->queue_declare($this->queue, false, $durable, false, false, false, new AMQPTable([$arguments]));
    }

    /**
     * Catch a channel inside the connection
     * @return AMQPChannel
     */
    public function declareChannel(): AMQPChannel
    {
        $this->channel = reset($this->manager->getConnections())->channel();
        $this->channel->basic_qos(null, self::PREFETCH_COUNT, null);
        return $this->channel;
    }

    /**
     * Publish a message
     * @param AMQPMessage $message
     * @param string $queueName
     * @param array $options
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function publish(AMQPMessage $message, string $queueName, array $options = [])
    {
        try {
            $this->declareChannel();
            $queue = $this->declareQueue($queueName, AMQP_DURABLE, $options);
            $this->channel->basic_publish($message, self::EXCHANGE_NAME, $queueName);
        } catch (\Exception $exception) {
            $this->getExceptionHandler()($exception, ['headers' => $message->getHeaders(), 'body' => $message->getBody()]);
        }
    }

    /**
     * AMQP Exchange is the publishing mechanism
     */
    public function declareExchange($exchange)
    {
        $this->exchange = $exchange;
        $this->exchange->setName(self::EXCHANGE_NAME);
        $this->channel->exchange_declare($this->exchange->getName(), AMQPExchangeType::DIRECT, false, true, false);
        $this->channel->queue_bind($this->queue, $this->exchange->getName(), $this->queue);
    }
}