<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use JMS\Serializer\DeserializationContext;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueManager;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Service;

class AmqpQueueDriver extends Service implements PurgeableQueueDriverInterface
{
    use UsesSerializer;

    /**
     * Maximum amount of time (in seconds) to wait for a message.
     */
    const WAIT_TIMEOUT = 10;
    const WAIT_PERIOD = 0.2;

    /**
     * @var \AMQPQueue
     */
    private $queue;

    /**
     * @var \AMQPEnvelope
     */
    private $currentEnvelope;

    private $dequeueingTimeMs = 0;

    /**
     * @var string
     */
    private $format;
    /**
     * @var QueueManager
     */
    private $manager;

    /**
     * AmqpQueueDriver constructor.
     *
     * @param QueueManager $manager
     */
    public function __construct(QueueManager $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    public function __destruct()
    {
        if ($this->currentEnvelope) {
            trigger_error('AmqpQueueDriver: A message was left unacknowledged.');
        }

        $this->doDestroy();
    }

    /**
     * {@inheritdoc}
     */
    public function configure($host, $username, $password, $format = self::FORMAT_JSON)
    {
        $this->format = $format;
    }

    /**
     * Opens a connection with a queuing system.
     */
    public function connect()
    {
        if (!$this->isConnected()) {
            $this->manager->connect();
        }
    }

    /**
     * Destroys the connection with the queuing system.
     */
    public function disconnect()
    {
        if ($this->currentEnvelope) {
            throw new \RuntimeException('Trying to disconnect with an unacknoleged message.');
        }

        $this->manager->disconnect();
    }

    /**
     * Returns true if a connection already exists with the queing system, false otherwise.
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->manager->isConnected();
    }

    /**
     * Returns true if a subscription already exists, false otherwise.
     *
     * @return bool
     */
    public function isSubscribed()
    {
        return null !== $this->queue;
    }

    /**
     * Creates a subscription to the given $queue, allowing to receive messages from it.
     *
     * @param string $queue Queue to subscribe
     */
    public function subscribe($queue)
    {
        if ($this->currentEnvelope) {
            throw new \RuntimeException('Don\'t be greedy and process the message you already have!');
        }

        $this->queue = $this->manager->getQueue((string) $queue);
    }

    /**
     * Destroys the created subscription with a queue.
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

    public function createQueueMessage()
    {
        $msg = new QueueMessage();
        $msg->setContext(new Context());

        return $msg;
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
        $this->manager->send(
            $destination ?? $message->getQueue(),
            $this->getSerializer()->serialize($message, $this->format),
            $message->getHeaders()
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function receive()
    {
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

        /** @var QueueMessageInterface $msg */
        $msg = $this->getSerializer()->deserialize($this->currentEnvelope->getBody(), SerializableInterface::class, $this->format, $deserializationContext);

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
}
