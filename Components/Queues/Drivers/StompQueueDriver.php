<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\Connection;
use CentralDesktop\Stomp\Frame;
use CentralDesktop\Stomp\Message\Bytes;
use JMS\Serializer\DeserializationContext;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Class StompQueueDriver.
 */
class StompQueueDriver extends Service implements QueueDriverInterface
{
    use UsesSerializer;

    const READ_TIMEOUT = 2;
    const BUFFER_SIZE = 1048576;

    const HEADER_SELECTOR = 'selector';

    /** @var Connection */
    protected $writeConnection;

    /** @var Connection */
    protected $readConnection;

    /** @var Frame */
    protected $currentFrame = null;

    /** @var int */
    protected $subscriptionId = null;

    /** @var string */
    protected $subscribedQueue = null;

    /** @var string */
    protected $format = QueueDriverInterface::FORMAT_JSON;

    /** @var string */
    protected $host;

    /** @var string */
    protected $username;

    /** @var string */
    protected $pass;

    /** @var string */
    protected $stompVersion;

    /** @var bool */
    protected $urlEncodeDestination = false;

    /** @var ConnectionStrategyFactory */
    protected $connectionStrategyFactory;

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

    public function setConnectionStrategyFactory(ConnectionStrategyFactory $connectionStrategyFactory)
    {
        $this->connectionStrategyFactory = $connectionStrategyFactory;
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
    public function configure($host, $username, $password, $format = QueueDriverInterface::FORMAT_JSON, $version = 1.1)
    {
        $this->format = $format;
        $this->host = $host;
        $this->username = $username;
        $this->pass = $password;
        $this->stompVersion = $version;
    }

    public function __destruct()
    {
        $this->disconnectRead();
        $this->disconnectWrite();
    }

    /**
     * @param int $seconds
     * @param int $milliseconds
     */
    public function setReadTimeout($seconds, $milliseconds = 0)
    {
        if (!$this->readConnection) {
            throw new \RuntimeException('You must connect and subscribe before setting the timeout.');
        }

        $this->readConnection->setReadTimeout($seconds, $milliseconds);
    }

    /**
     * Connect read socket.
     *
     * @throws \CentralDesktop\Stomp\Exception
     */
    protected function connectRead()
    {
        if (!$this->readConnection) {
            $strategy = $this->connectionStrategyFactory->createConnectionStrategy($this->host);
            $this->readConnection = new Connection($strategy);
            $this->readConnection->setReadTimeout(self::READ_TIMEOUT);
            $this->readConnection->setBufferSize(self::BUFFER_SIZE);
            $connectionOK = $this->readConnection->connect($this->username, $this->pass, $this->stompVersion);

            if (!$connectionOK) {
                throw new \RuntimeException(
                    sprintf(
                        'Could not connect to ActiveMQ in host "%s".',
                        $this->host
                    )
                );
            }
        }
    }

    /**
     * Connect write socket.
     *
     * @throws \CentralDesktop\Stomp\Exception
     */
    protected function connectWrite()
    {
        if (!$this->writeConnection) {
            $strategy = $this->connectionStrategyFactory->createConnectionStrategy($this->host);
            $this->writeConnection = new Connection($strategy);
            $this->writeConnection->setBufferSize(self::BUFFER_SIZE);
            $connectionOK = $this->writeConnection->connect($this->username, $this->pass, $this->stompVersion);

            if (!$connectionOK) {
                throw new \RuntimeException(
                    sprintf(
                        'Could not connect to ActiveMQ in host "%s".',
                        $this->host
                    )
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        // write connection is eager because it must be fast,
        // read connection will be opened automatically on demand when subscribing
        $this->connectWrite();
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->isWriteConnected();
    }

    protected function isWriteConnected()
    {
        return $this->writeConnection && $this->writeConnection->isConnected();
    }

    protected function isReadConnected()
    {
        return $this->readConnection && $this->readConnection->isConnected();
    }

    protected function disconnectRead()
    {
        if ($this->isReadConnected()) {
            $this->readConnection->disconnect();
        }
        $this->readConnection = null;
    }

    protected function disconnectWrite()
    {
        if ($this->isWriteConnected()) {
            $this->writeConnection->disconnect();
        }
        $this->subscriptionId = null;
        $this->subscribedQueue = null;
        $this->currentFrame = null;
        $this->writeConnection = null;
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->isWriteConnected()) {
            $this->disconnectWrite();
        }
    }

    public function isSubscribed()
    {
        return $this->isReadConnected() && $this->subscriptionId;
    }

    /** {@inheritdoc} */
    public function subscribe($queue, $selector = null, $prefetchSize = 1)
    {
        if($this->urlEncodeDestination){
            $destination = urlencode($queue);
        }else{
            $destination = $queue;
        }

        if (!is_numeric($prefetchSize) || $prefetchSize < 0) {
            throw new \InvalidArgumentException('Invalid prefetchSize, a non negative integer is expected');
        }

        if ($this->subscriptionId) {
            throw new \RuntimeException('StompQueueDriver: A subscription already exists in the current connection');
        }

        $this->connectRead();

        $this->subscriptionId = uniqid();
        $this->subscribedQueue = $queue;

        $properties = [
            'id' => $this->subscriptionId,
        ];

        if ($selector) {
            $properties[self::HEADER_SELECTOR] = $selector;
        }

        $this->readConnection->prefetchSize = $prefetchSize;
        $this->readConnection->subscribe($destination, $properties);
    }

    /**
     * {@inheritdoc}
     */
    public function unSubscribe()
    {
        if ($this->isSubscribed()) {
            $destination = $this->subscribedQueue;

            if($this->urlEncodeDestination){
                $destination = urlencode($this->subscribedQueue);
            }

            $properties = [
                'id' => $this->subscriptionId,
            ];
            $this->readConnection->readFrame();
            $this->readConnection->unsubscribe($destination, $properties);
            $this->subscriptionId = null;
            $this->currentFrame = null;
            $this->subscribedQueue = null;
        }
    }

    /** {@inheritdoc} */
    public function send(QueueMessageInterface $message, $destination = null)
    {
        $destination = $destination ? $destination : $message->getQueue();
        if($this->urlEncodeDestination){
            $destination = urlencode($destination);
        }

        $this->checkConnection();

        $serializedMsg = $this->getSerializer()->serialize($message, $this->format);

        return $this->writeConnection->send($destination, new Bytes($serializedMsg, $message->getHeaders()), null, true);
    }

    /** {@inheritdoc} */
    public function receive()
    {
        $this->checkSubscription();

        if ($this->currentFrame) {
            throw new \RuntimeException(
                'StompQueueDriver: This driver has a message that was not acknowledged yet. A message must be processed and acknowledged before receiving new messages.'
            );
        }

        $this->currentFrame = $this->readConnection->readFrame();

        // If we got frames of an old subscription, ignore them
        while ($this->currentFrame && $this->currentFrame->headers['subscription'] != $this->subscriptionId) {
            $this->currentFrame = $this->readConnection->readFrame();
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
            $msg = $this->getSerializer()->deserialize($this->currentFrame->body, SerializableInterface::class, $this->format, $deserializationContext);

            foreach ($this->currentFrame->headers as $header => $value) {
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
        $this->checkSubscription();

        if (!$this->currentFrame) {
            throw new \RuntimeException('You must first receive a message, before acknowledging it');
        }

        $this->readConnection->ack($this->currentFrame);
        $this->currentFrame = null;
    }

    /** {@inheritdoc} */
    public function nack()
    {
        $this->checkSubscription();

        if (!$this->currentFrame) {
            throw new \RuntimeException('You must first receive a message, before nacking it');
        }

        $this->readConnection->nack($this->currentFrame);
        $this->currentFrame = null;
    }

    protected function checkConnection()
    {
        if (!$this->isConnected()) {
            throw new \RuntimeException('StompQueueDriver: A connection must be opened before sending data');
        }
    }

    protected function checkSubscription()
    {
        $this->checkConnection();

        if (!$this->isSubscribed()) {
            throw new \RuntimeException('StompQueueDriver: A subscription must be established before performing this operation');
        }
    }

    public function createQueueMessage()
    {
        $msg = new QueueMessage();
        $msg->setContext(new Context());

        /*
         * By default, messages created with this driver would be sent to the subscribed queue
         */
        if ($this->subscribedQueue) {
            $msg->setQueue($this->subscribedQueue);
        }

        return $msg;
    }

    /**
     * {@inheritdoc}
     */
    public function doDestroy()
    {
        $this->disconnect();
        $this->unSubscribe();
    }

    /**
     * Close the opened connections on kernel terminate.
     *
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this->doDestroy();
    }

    /**
     * Calls the doDestroy method on console.terminate event.
     *
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $this->doDestroy();
    }
}
