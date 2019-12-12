<?php


namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

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

class PhpAmqpLibDriver extends Service implements PurgeableQueueDriverInterface
{
    use UsesSerializer;
    use UsesExceptionHandlerTrait;
    use ContainerAwareTrait;
    
    /**
     * Maximum amount of time (in seconds) to wait for a message.
     */
    const WAIT_TIMEOUT = 10;
    const WAIT_PERIOD = 0.2;
    const DEFAULT_PORT = 5672;

    /**
     * @var \AMQPQueue
     */
    private $queue;

    /**
     * @var \AMQPEnvelope
     */
    private $currentEnvelope;

    /**
     * @var int Time in ms
     */
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

    private $connectionId = [];

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

    public function __destruct()
    {
        if ($this->currentEnvelope) {
            trigger_error('PhpAmqpLibDriver: A message was left unacknowledged');
        }

        $this->doDestroy();
    }

    public function configure($host, $username, $password, $format = self::FORMAT_JSON, $port = null, $vhost = null)
    {
        $this->format = $format;
    }

//    public function connect()
//    {
//        return AMQPStreamConnection::create_connection([
//           [
//               'host' => $this->host,
//               'port' => $this->port,
//               'user' => $this->username,
//               'password' => $this->password,
//               'vhost' => $vhost
//           ]
//        ],
//        [
//            'insist' => false,
//            'login_method' => 'AMQPLAIN',
//            'login_response' => null,
//            'locale' => 'en_US',
//            'connection_timeout' => 3.0,
//            'read_write_timeout' => 3.0,
//            'context' => null,
//            'keepalive' => false,
//            'heartbeat' => 0
//        ]);
//    }
    public function connect()
    {
        $this->manager->connect();
    }


    public function disconnect()
    {
        if ($this->currentEnvelope) {
            throw new \RuntimeException('Trying to disconnect with an unacknoleged message.');
        }

        $this->manager->disconnect();
    }
    
    
    public function createQueueMessage()
    {
        $msg = new QueueMessage();
        $msg->setContext(new Context());

        return $msg;
    }

    public function isConnected()
    {
        return $this->manager->isConnected();
    }
    
    public function isSubscribed()
    {
        return null !== $this->queue;
    }
    
    public function subscribe($queue)
    {
        if ($this->currentEnvelope) {
            throw new \RuntimeException('Don\'t be greedy and process the message you already have!');
        }

        $this->queue = $this->manager->getQueue((string) $queue);
    }

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
    
    private function fillByContainer()
    {
        $this->container->get();
    } 
    
    public function getManager()
    {
        return $this->manager;
    }
    
    public function setQueue(string $queueName, int $flag = AMQP_DURABLE, array $options = [])
    {
        $this->manager->declareQueue($queueName, $flag, $options);
    }

    public function setExchange($exchange)
    {
        $this->manager->declareExchange($exchange);
    }

    public function setChannel()
    {
        $this->manager->declareChannel();
    }
}