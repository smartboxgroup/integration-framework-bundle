<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use JMS\Serializer\DeserializationContext;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Stomp\Client;
use Stomp\StatefulStomp;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;
use Stomp\Exception\ConnectionException;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Class StompQueueDriver.
 */
class StompQueueDriver extends Service implements QueueDriverInterface
{
    use UsesSerializer;
    use UsesExceptionHandlerTrait;

    const STOMP_VERSION = '1.1';

    /** @var \Stomp\Transport\Frame */
    protected $currentFrame = null;

    /** @var string */
    protected $format = QueueDriverInterface::FORMAT_JSON;

    /** @var string */
    protected $host;

    /** @var string */
    protected $username;

    /** @var string */
    protected $pass;

    /** @var string */
    protected $stompVersion = self::STOMP_VERSION;

    /** @var bool */
    protected $urlEncodeDestination = false;

    /** @var StatefulStomp */
    protected $statefulStomp;

    /** @var int */
    protected $timeout;

    /** @var string */
    protected $vhost;

    /** @var bool|int  */
    protected $subscriptionId = false;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var int The time it took in ms to deserialize the message
     */
    protected $dequeueingTimeMs = 0;

    /** @var bool */
    protected $sync;

    /**
     * @return bool
     */
    public function isUrlEncodeDestination()
    {
        return $this->urlEncodeDestination;
    }

    /**
     * @param bool $urlEncodeDestination
     */
    public function setUrlEncodeDestination($urlEncodeDestination)
    {
        $this->urlEncodeDestination = $urlEncodeDestination;
    }

    /**
     * @return string
     */
    public function getStompVersion()
    {
        return $this->stompVersion;
    }

    /**
     * Set the version to Stomp driver
     *
     * @param string $version
     */
    public function setStompVersion(string $version = self::STOMP_VERSION)
    {
        $this->stompVersion = $version;
    }

    /**
     * Set the timeout to Stomp driver
     *
     * @param int $timeout
     */
    public function setTimeout(int $timeout = 3)
    {
        $this->timeout = $timeout;
    }

    /**
     * Set if the driver will work in synchronous or asynchronous mode
     *
     * @param bool $sync
     */
    public function setSync(bool $sync = true)
    {
        $this->sync = $sync;
    }
    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @param string $pass
     */
    public function setPass($pass)
    {
        $this->pass = $pass;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
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
     * @return mixed
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return mixed
     */
    public function getVhost()
    {
        return $this->vhost;
    }

    /**
     * @return int
     */
    public function getDequeueingTimeMs()
    {
        return $this->dequeueingTimeMs;
    }

    /**
     * @param $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /** {@inheritdoc} */
    public function configure(string $host, string $username, string $password, string $vhost = null)
    {
        $this->host = $host;
        $this->username = $username;
        $this->pass = $password;
        $this->vhost = $vhost;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * @param int $seconds
     */
    public function setReadTimeout($seconds)
    {
        $this->timeout = $seconds;
        $this->statefulStomp->getClient()->getConnection()->setReadTimeout($this->timeout);
    }

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        if (!$this->isConnected()) {
            $client = new Client($this->host);
            $client->setLogin($this->getUsername(), $this->getPass());
            $client->setReceiptWait($this->timeout);
            $client->setSync($this->sync);
            $client->getConnection()->setReadTimeout($this->timeout);
            $client->setVersions([$this->stompVersion]);
            $client->setVhostname($this->vhost);
            $this->statefulStomp = new StatefulStomp($client);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->statefulStomp && $this->statefulStomp->getClient()->isConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->subscriptionId = false;
        if ($this->statefulStomp) {
            $this->statefulStomp->getClient()->disconnect(true);
        }
    }

    public function isSubscribed()
    {
        return false !== $this->subscriptionId;
    }

    /** {@inheritdoc} */
    public function subscribe($destination, $selector = null)
    {
        $destinationUri = $destination;
        if ($this->urlEncodeDestination) {
            $destinationUri = urlencode($destination);
        }

        $this->subscriptionId = $this->statefulStomp->subscribe($destinationUri, $selector, 'client-individual');
    }

    /**
     * {@inheritdoc}
     */
    public function unSubscribe()
    {
        if ($this->isSubscribed()) {
            $this->statefulStomp->unsubscribe($this->subscriptionId);
            $this->subscriptionId = false;
            $this->currentFrame = null;
        }
        //Top purge the queue independently of the Queuing system, the safest way is to disconnect
        $this->disconnect();
    }

    /** {@inheritdoc} */
    public function send(QueueMessageInterface $message, $destination = null, array $arguments = [])
    {
        $destination = $destination ? $destination : $message->getQueue();
        $destinationUri = $destination;
        if ($this->urlEncodeDestination) {
            $destinationUri = urlencode($destination);
        }

        $serializedMsg = $this->getSerializer()->serialize($message, $this->format);

        try {
            return $this->statefulStomp->send($destinationUri, new Message($serializedMsg, $message->getHeaders()));
        } catch (ConnectionException $e) {
            $this->dropConnection();

            throw $e;
        }
    }

    protected function isFrameFromSubscription(Frame $frame)
    {
        $headers = $frame->getHeaders();

        return array_key_exists('subscription', $headers) && $headers['subscription'] == $this->subscriptionId;
    }

    /** {@inheritdoc} */
    public function receive()
    {
        if ($this->currentFrame) {
            throw new \RuntimeException(
                'StompQueueDriver: This driver has a message that was not acknowledged yet. A message must be processed and acknowledged before receiving new messages.'
            );
        }

        $this->dequeueingTimeMs = 0;

        $this->currentFrame = $this->statefulStomp->read();

        while ($this->currentFrame && !$this->isFrameFromSubscription($this->currentFrame)) {
            $this->currentFrame = $this->statefulStomp->read();
        }

        $msg = null;

        if ($this->currentFrame) {
            $start = microtime(true);
            $deserializationContext = new DeserializationContext();
            if (!empty($version)) {
                $deserializationContext->setVersion($version);
            }

            if (!empty($group)) {
                $deserializationContext->setGroups([$group]);
            }
            try {
                /** @var QueueMessageInterface $msg */
                $msg = $this->getSerializer()->deserialize($this->currentFrame->getBody(), SerializableInterface::class, $this->format, $deserializationContext);
            } catch (\Exception $exception) {
                $this->getExceptionHandler()($exception, ['headers' => $this->currentFrame->getHeaders(), 'body' => $this->currentFrame->getBody()]);
                $this->ack();
                $this->markDequeuedTime($start);
                return null;
            }
            foreach ($this->currentFrame->getHeaders() as $header => $value) {
                $msg->setHeader($header, $this->unescape($value));
            }

            $this->markDequeuedTime($start);
        }

        return $msg;
    }

    private function unescape($string)
    {
        return str_replace(['\r', '\n', '\c', '\\\\'], ["\r", "\n", ':', '\\'], $string);
    }

    /** {@inheritdoc} */
    public function ack(QueueMessageInterface $message = null)
    {
        if (!$this->currentFrame) {
            throw new \RuntimeException('You must first receive a message, before acking it');
        }

        $this->statefulStomp->ack($this->currentFrame);
        $this->currentFrame = null;
    }

    /** {@inheritdoc} */
    public function nack(QueueMessageInterface $message = null)
    {
        if (!$this->currentFrame) {
            throw new \RuntimeException('You must first receive a message, before nacking it');
        }

        $this->statefulStomp->nack($this->currentFrame);
        $this->currentFrame = null;
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
    public function destroy()
    {
        $this->disconnect();
    }

    /**
     * Close the opened connections on kernel terminate.
     *
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this->disconnect();
    }

    /**
     * Calls the doDestroy method on console.terminate event.
     *
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $this->disconnect();
    }

    /**
     * Kill the TCP connection directly.
     */
    protected function dropConnection()
    {
        $this->statefulStomp->getClient()->getConnection()->disconnect();
    }

    /**
     * @param float $start
     */
    private function markDequeuedTime($start)
    {
        $this->dequeueingTimeMs = (int) ((microtime(true) - $start) * 1000);
    }
}
