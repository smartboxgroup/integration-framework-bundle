<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Amqp\AmqpQueue;
use Interop\Queue\Context as ConnectionContext;
use JMS\Serializer\DeserializationContext;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Service;

class AmqpQueueDriver extends Service implements PurgeableQueueDriverInterface
{
    use UsesSerializer;

    /**
     * @var \Enqueue\AmqpExt\AmqpContext|ConnectionContext
     */
    private $connectionContext;

    /**
     * @var string
     */
    private $format;

    /**
     * @var \Interop\Queue\Queue
     */
    private $queue;

    /**
     * @var \Interop\Queue\Message
     */
    private $currentEnvelope;

    private $dequeueingTimeMs = 0;

    /**
     * Configures the driver.
     *
     * @param string $host
     * @param string $username
     * @param string $password Password to connect to the queuing system
     * @param string $format
     * @param int    $port
     * @param string $vhost
     */
    public function configure($host, $username, $password, $format = self::FORMAT_JSON, $port = 5672, $vhost = '/')
    {
        $this->format = $format;
        $factory = new AmqpConnectionFactory([
            'host' => $host,
            'port' => $port,
            'vhost' => $vhost,
            'user' => $username,
            'pass' => $password,
        ]);

        $this->connectionContext = $factory->createContext();
    }

    /**
     * Opens a connection with a queuing system.
     */
    public function connect()
    {
    }

    /**
     * Destroys the connection with the queuing system.
     */
    public function disconnect()
    {
        $this->connectionContext->close();
    }

    /**
     * Returns true if a connection already exists with the queing system, false otherwise.
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->connectionContext->getExtChannel()->isConnected();
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
        $this->queue = $this->connectionContext->createQueue($queue);
        $this->queue->addFlag(AmqpQueue::FLAG_DURABLE);
        $this->connectionContext->declareQueue($this->queue);
    }

    /**
     * Destroys the created subscription with a queue.
     */
    public function unSubscribe()
    {
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

        $this->getConsumer()->acknowledge($this->currentEnvelope);
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

        $this->getConsumer()->reject($this->currentEnvelope, true);
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
        $this->subscribe($destination ?? $message->getQueue());

        $msg = $this->connectionContext->createMessage($this->getSerializer()->serialize($message, $this->format), [], $message->getHeaders());

        $this->connectionContext->createProducer()->send($this->queue, $msg);

        return true;
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
    public function receive()
    {
        if ($this->currentEnvelope) {
            throw new \RuntimeException(
                'AmqpQueueDriver: This driver has a message that was not acknowledged yet. A message must be processed and acknowledged before receiving new messages.'
            );
        }

        $this->dequeueingTimeMs = 0;

        $this->currentEnvelope = $this->getConsumer()->receiveNoWait();

        $msg = null;

        if (!$this->currentEnvelope) {
            return null;
        }

        $start = microtime(true);
        $deserializationContext = new DeserializationContext();
        if (!empty($version)) {
            $deserializationContext->setVersion($version);
        }

        if (!empty($group)) {
            $deserializationContext->setGroups([$group]);
        }

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
     * @param ConnectionContext $connectionContext
     */
    public function setConnectionContext(ConnectionContext $connectionContext)
    {
        $this->connectionContext = $connectionContext;
    }

    /**
     * @return \Enqueue\AmqpExt\AmqpConsumer|\Interop\Queue\Consumer
     */
    private function getConsumer()
    {
        return $this->connectionContext->createConsumer($this->queue);
    }

    /**
     * Remove every messages in the defined queue.
     *
     * @param string $queue The queue name
     *
     * @throws \Interop\Queue\Exception\PurgeQueueNotSupportedException
     */
    public function purge(string $queue)
    {
        $this->connectionContext->purgeQueue($this->connectionContext->createQueue($queue));
    }
}
