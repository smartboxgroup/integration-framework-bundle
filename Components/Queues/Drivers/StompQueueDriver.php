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
use Stomp\Transport\Message;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Class StompQueueDriver.
 */
class StompQueueDriver extends Service implements QueueDriverInterface
{
    use UsesSerializer;

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
    protected $stompVersion = '1.1';

    /** @var bool */
    protected $urlEncodeDestination = false;

    /** @var  StatefulStomp */
    protected $statefulStomp;

    protected $timeout = 3;

    protected $vhost;

    /**
     * @return boolean
     */
    public function isUrlEncodeDestination()
    {
        return $this->urlEncodeDestination;
    }

    /**
     * @param boolean $urlEncodeDestination
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
    public function configure($host, $username, $password, $format = QueueDriverInterface::FORMAT_JSON, $version = 1.1, $vhost = null)
    {
        $this->format = $format;
        $this->host = $host;
        $this->username = $username;
        $this->pass = $password;
        $this->stompVersion = $version;
        $this->vhost = $vhost;

        $client = new Client($this->host);
        $client->setLogin($this->getUsername(),$this->getPass());
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
        if($this->statefulStomp){
            $this->statefulStomp->getClient()->disconnect(true);
        }
    }

    public function isSubscribed()
    {
        return $this->statefulStomp && $this->statefulStomp->getSubscriptions()->count() > 0;
    }

    /** {@inheritdoc} */
    public function subscribe($destination, $selector = null, $prefetchSize = 1)
    {
        if($this->urlEncodeDestination){
            $destinationUri = urlencode($destination);
        }else{
            $destinationUri = $destination;
        }

        $this->statefulStomp->subscribe($destinationUri,$selector,'client-individual');
    }

    /**
     * {@inheritdoc}
     */
    public function unSubscribe()
    {
        $this->statefulStomp->unsubscribe();
    }

    /** {@inheritdoc} */
    public function send(QueueMessageInterface $message, $destination = null)
    {
        $destination = $destination ? $destination : $message->getQueue();
        if($this->urlEncodeDestination){
            $destinationUri = urlencode($destination);
        }else{
            $destinationUri = $destination;
        }

        $serializedMsg = $this->getSerializer()->serialize($message, $this->format);
        return $this->statefulStomp->send($destinationUri, new Message($serializedMsg, $message->getHeaders()));
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
