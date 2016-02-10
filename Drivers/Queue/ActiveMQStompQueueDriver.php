<?php

namespace Smartbox\Integration\FrameworkBundle\Drivers\Queue;

use CentralDesktop\Stomp\Connection;
use CentralDesktop\Stomp\Frame;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Messages\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Messages\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\Traits\UsesSerializer;
use CentralDesktop\Stomp\ConnectionFactory\Simple as SimpleConnectionStrategy;
use CentralDesktop\Stomp\Message\Bytes;
use JMS\Serializer\DeserializationContext;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Class ActiveMQStompQueueDriver
 * @package Smartbox\Integration\FrameworkBundle\Drivers\Queue
 */
class ActiveMQStompQueueDriver extends Service implements QueueDriverInterface {
    use UsesSerializer;

    const READ_TIMEOUT = 2;
    const BUFFER_SIZE = 1048576;
    const DEFAULT_PORT = 61613;

    const HEADER_SELECTOR = 'selector';

    /** @var  Connection */
    protected $writeConnection;

    /** @var  Connection */
    protected $readConnection;

    /** @var Frame */
    protected $currentFrame = null;

    /** @var int */
    protected $subscriptionId = null;

    /** @var string */
    protected $subscribedQueue = null;

    /** @var string  */
    protected $format = QueueDriverInterface::FORMAT_JSON;

    /** @var  string */
    protected $host;

    /** @var  string */
    protected $port;

    /** @var  string */
    protected $username;

    /** @var  string */
    protected $pass;

    /** @var string */
    protected $stompVersion;

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $port
     */
    public function setPort($port)
    {
        $this->port = $port;
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

    /** {@inheritDoc} */
    public function configure($host, $username, $password,$format = QueueDriverInterface::FORMAT_JSON, $port = self::DEFAULT_PORT , $version = 1.1){
        $this->format = $format;
        $this->host = $host;
        $this->port = $port;
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
    public function setReadTimeout($seconds, $milliseconds = 0){
        if (!$this->readConnection) {
            throw new \RuntimeException('You must connect and subscribe before setting the timeout.');
        }

        $this->readConnection->setReadTimeout($seconds, $milliseconds);
    }

    /**
     * Connect read socket
     *
     * @throws \CentralDesktop\Stomp\Exception
     */
    protected function connectRead()
    {
        if (!$this->readConnection) {
            $strategy = new SimpleConnectionStrategy("tcp://$this->host:$this->port");
            $this->readConnection = new Connection($strategy);
            $this->readConnection->setReadTimeout(self::READ_TIMEOUT);
            $this->readConnection->setBufferSize(self::BUFFER_SIZE);
            $this->readConnection->connect($this->username, $this->pass, $this->stompVersion);
        }
    }

    /**
     * Connect write socket
     *
     * @throws \CentralDesktop\Stomp\Exception
     */
    protected function connectWrite()
    {
        if (!$this->writeConnection) {
            $strategy = new SimpleConnectionStrategy("tcp://$this->host:$this->port");
            $this->writeConnection = new Connection($strategy);
            $this->writeConnection->setBufferSize(self::BUFFER_SIZE);
            $this->writeConnection->connect($this->username, $this->pass, $this->stompVersion);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        // write connection is eager because it must be fast,
        // read connection will be opened automatically on demand when subscribing
        $this->connectWrite();
    }

    /**
     * {@inheritDoc}
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
        if($this->isReadConnected()) {
            $this->readConnection->disconnect();
        }
        $this->readConnection = null;
    }

    protected function disconnectWrite()
    {
        if($this->isWriteConnected()) {
            $this->writeConnection->disconnect();
        }
        $this->subscriptionId = null;
        $this->subscribedQueue = null;
        $this->currentFrame = null;
        $this->writeConnection = null;
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect()
    {
        if($this->isWriteConnected()) {
            $this->disconnectWrite();
        }
    }

    public function isSubscribed()
    {
        return $this->isReadConnected() && $this->subscriptionId;
    }

    /** {@inheritDoc} */
    public function subscribe($queue, $selector = null, $prefetchSize = 1)
    {
        if (!is_numeric($prefetchSize) || $prefetchSize < 0) {
            throw new \InvalidArgumentException('Invalid prefetchSize, a non negative integer is expected');
        }

        if ($this->subscriptionId) {
            throw new \RuntimeException("ActiveMQStompQueueDriver: A subscription already exists in the current connection");
        }

        $this->connectRead();

        $this->subscriptionId = uniqid();
        $this->subscribedQueue = $queue;

        $properties = array(
            'id' => $this->subscriptionId
        );

        if ($selector) {
            $properties[self::HEADER_SELECTOR] = $selector;
        }

        $this->readConnection->prefetchSize = $prefetchSize;
        $this->readConnection->subscribe($queue, $properties);
    }

    /**
     * {@inheritDoc}
     */
    public function unSubscribe()
    {
        if ($this->isSubscribed()) {
            $properties = array(
                'id' => $this->subscriptionId
            );
            $this->readConnection->readFrame();
            $this->readConnection->unsubscribe($this->subscribedQueue, $properties);
            $this->subscriptionId = null;
            $this->currentFrame = null;
            $this->subscribedQueue = null;
        }
    }

    /** {@inheritDoc} */
    public function send(QueueMessageInterface $message)
    {
        $this->checkConnection();

        $serializedMsg = $this->getSerializer()->serialize($message, $this->format);

        return $this->writeConnection->send($message->getQueue(), new Bytes($serializedMsg, $message->getHeaders()), null, true);
    }

    /** {@inheritDoc} */
    public function receive()
    {
        $this->checkSubscription();

        if ($this->currentFrame) {
            throw new \RuntimeException(
                "ActiveMQStompQueueDriver: This connector has a message that was not acknowledged yet. A message must be processed and acknowledged before receiving new messages."
            );
        }

        $this->currentFrame = $this->readConnection->readFrame();

        // If we got frames of an old subscription, ignore them
        while($this->currentFrame && $this->currentFrame->headers['subscription'] != $this->subscriptionId){
            $this->currentFrame = $this->readConnection->readFrame();
        }

        $msg = null;

        if ($this->currentFrame) {
            $deserializationContext = new DeserializationContext();
            if (!empty($version)) {
                $deserializationContext->setVersion($version);
            }

            if (!empty($group)) {
                $deserializationContext->setGroups(array($group));
            }

            /** @var QueueMessageInterface $msg */
            $msg = $this->getSerializer()->deserialize($this->currentFrame->body, SerializableInterface::class, $this->format, $deserializationContext);

            foreach($this->currentFrame->headers as $header => $value){
                $msg->setHeader($header,$this->unescape($value));
            }
        }

        return $msg;
    }

    private function unescape($string){
        return str_replace(['\r','\n','\c','\\\\'],["\r","\n",":",'\\'],$string);
    }

    /** {@inheritDoc} */
    public function ack()
    {
        $this->checkSubscription();

        if(!$this->currentFrame){
            throw new \RuntimeException("You must first receive a message, before acknowledging it");
        }

        $this->readConnection->ack($this->currentFrame);
        $this->currentFrame = null;
    }

    /** {@inheritDoc} */
    public function nack()
    {
        $this->checkSubscription();

        if(!$this->currentFrame){
            throw new \RuntimeException("You must first receive a message, before nacking it");
        }

        $this->readConnection->nack($this->currentFrame);
        $this->currentFrame = null;
    }

    protected function checkConnection(){
        if(!$this->isConnected()){
            throw new \RuntimeException("ActiveMQStompQueueDriver: A connection must be opened before sending data");
        }
    }

    protected function checkSubscription(){
        $this->checkConnection();

        if(!$this->isSubscribed()){
            throw new \RuntimeException("ActiveMQStompQueueDriver: A subscription must be established before performing this operation");
        }
    }

    public function createQueueMessage()
    {
        $msg = new QueueMessage();
        $msg->setContext(new Context([Context::VERSION => $this->getFlowsVersion()]));

        /**
         * By default, messages created with this driver would be sent to the subscribed queue
         */
        if($this->subscribedQueue){
            $msg->setQueue($this->subscribedQueue);
        }

        return $msg;
    }

    /**
     * {@inheritDoc}
     */
    public function doDestroy()
    {
        $this->disconnect();
        $this->unSubscribe();
    }

    /**
     * Close the opened connections on kernel terminate
     *
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this->doDestroy();
    }

    /**
     * Calls the doDestroy method on console.terminate event
     *
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $this->doDestroy();
    }
}
