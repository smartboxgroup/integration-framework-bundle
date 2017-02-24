<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use JMS\Serializer\DeserializationContext;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Stomp\Client;
use Stomp\StatefulStomp;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Class StompQueueDriver.
 */
class StompQueueDriver extends Service implements QueueDriverInterface
{
    use UsesSerializer;

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

    protected $timeout = 3;

    protected $vhost;

    protected $subscriptionId = false;

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
     * @param string $stompVersion
     */
    public function setStompVersion($stompVersion)
    {
        $this->stompVersion = $stompVersion;
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
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /** {@inheritdoc} */
    public function configure($host, $username, $password, $format = QueueDriverInterface::FORMAT_JSON, $version = self::STOMP_VERSION, $vhost = null,$timeout = 3)
    {
        $this->format = $format;
        $this->host = $host;
        $this->username = $username;
        $this->pass = $password;
        $this->stompVersion = $version;
        $this->vhost = $vhost;
        $this->timeout = $timeout;

        $client = new Client($this->host);
        $client->setLogin($this->getUsername(), $this->getPass());
        $client->setReceiptWait($this->timeout);
        $client->setSync(true);
        $client->getConnection()->setReadTimeout($this->timeout);
        $client->setVersions([$version]);
        $client->setVhostname($vhost);
        $this->statefulStomp = new StatefulStomp($client);
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

    public function connect()
    {
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
        return $this->subscriptionId !== false;
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
    public function send(QueueMessageInterface $message, $destination = null)
    {
        $destination = $destination ? $destination : $message->getQueue();
        $destinationUri = $destination;
        if ($this->urlEncodeDestination) {
            $destinationUri = urlencode($destination);
        }

        $serializedMsg = $this->getSerializer()->serialize($message, $this->format);

        return $this->statefulStomp->send($destinationUri, new Message($serializedMsg, $message->getHeaders()));
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

        $this->currentFrame = $this->statefulStomp->read();

        while ($this->currentFrame && !$this->isFrameFromSubscription($this->currentFrame)) {
            $this->currentFrame = $this->statefulStomp->read();
        }

        $msg = null;

        if ($this->currentFrame) {
            $deserializationContext = new DeserializationContext();
            if (!empty($version)) {
                $deserializationContext->setVersion($version);
            }

            if (!empty($group)) {
                $deserializationContext->setGroups([$group]);
            }

            /** @var QueueMessageInterface $msg */
            $msg = $this->getSerializer()->deserialize($this->currentFrame->getBody(), SerializableInterface::class, $this->format, $deserializationContext);

            foreach ($this->currentFrame->getHeaders() as $header => $value) {
                $msg->setHeader($header, $this->unescape($value));
            }
        }

        return $msg;
    }

    private function unescape($string)
    {
        return str_replace(['\r', '\n', '\c', '\\\\'], ["\r", "\n", ':', '\\'], $string);
    }

    /** {@inheritdoc} */
    public function ack()
    {
        if (!$this->currentFrame) {
            throw new \RuntimeException('You must first receive a message, before acking it');
        }

        $this->statefulStomp->ack($this->currentFrame);
        $this->currentFrame = null;
    }

    /** {@inheritdoc} */
    public function nack()
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
    public function doDestroy()
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
}
