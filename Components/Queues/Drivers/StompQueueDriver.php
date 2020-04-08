<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Dtos\Message as MessageDto;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Service;
use Stomp\Client;
use Stomp\Exception\ConnectionException;
use Stomp\Network\Connection;
use Stomp\StatefulStomp;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Class StompQueueDriver.
 */
class StompQueueDriver extends Service implements SyncQueueDriverInterface
{
    const STOMP_VERSION = '1.1';

    const PREFETCH_COUNT = 1;

    const READ_TIMEOUT = 15;

    const CONNECTION_TIMEOUT = 30;

    /** @var Frame */
    protected $currentFrame;

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

    /** @var bool|int */
    protected $subscriptionId = false;

    /** @var @var int|null */
    protected $prefetchCount;

    /**
     * @var string
     */
    protected $description;

    /** @var bool */
    protected $sync;

    /** @var float */
    protected $readTimeout;

    /** @var @var float */
    protected $connectionTimeout;

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
     * Set the version to Stomp driver.
     */
    public function setStompVersion(string $version)
    {
        $this->stompVersion = $version;
    }

    /**
     * Set the timeout to Stomp driver.
     */
    public function setTimeout(int $connectionTimeout)
    {
        $this->connectionTimeout = $connectionTimeout;
    }

    /**
     * Set if the driver will work in synchronous or asynchronous mode.
     */
    public function setSync(bool $sync)
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
     * @param $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setPrefetchCount(int $prefetchCount = self::PREFETCH_COUNT)
    {
        $this->prefetchCount = $prefetchCount;
    }

    /**
     * @param int $seconds
     */
    public function setReadTimeout($seconds)
    {
        $this->readTimeout = $seconds;
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     */
    public function connect()
    {
        if (!$this->isConnected()) {
            $stompConnection = new Connection($this->host, $this->connectionTimeout);
            $client = new Client($stompConnection);
            $client->setLogin($this->getUsername(), $this->getPass());
            $client->setReceiptWait($this->readTimeout);
            $client->setSync($this->sync);
            $client->getConnection()->setReadTimeout($this->readTimeout);
            $client->setVersions([$this->stompVersion]);
            $client->setVhostname($this->vhost);
            $client->getProtocol()->setPrefetchCount($this->prefetchCount);
            $client->connect();

            $this->statefulStomp = new StatefulStomp($client);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool
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

    /**
     * {@inheritdoc}
     */
    public function subscribe(string $destination, $selector = null)
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

    /**
     * {@inheritdoc}
     */
    public function send(string $destination, string $body = '', array $headers = []): bool
    {
        if ($this->urlEncodeDestination) {
            $destination = urlencode($destination);
        }

        try {
            return $this->statefulStomp->send($destination, new Message($body, $headers));
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

    /**
     * {@inheritdoc}
     */
    public function receive()
    {
        if ($this->currentFrame) {
            throw new \RuntimeException('StompQueueDriver: This driver has a message that was not acknowledged yet. A message must be processed and acknowledged before receiving new messages.');
        }

        $this->currentFrame = $this->statefulStomp->read();

        while ($this->currentFrame && !$this->isFrameFromSubscription($this->currentFrame)) {
            $this->currentFrame = $this->statefulStomp->read();
        }

        if (false === $this->currentFrame) {
            return null;
        }

        $headers = [];
        foreach ($this->currentFrame->getHeaders() as $name => $value) {
            $headers[$name] = $this->unescape($value);
        }

        return new MessageDto($this->currentFrame->getBody(), $headers);
    }

    private function unescape($string)
    {
        return str_replace(['\r', '\n', '\c', '\\\\'], ["\r", "\n", ':', '\\'], $string);
    }

    /**
     * {@inheritdoc}
     */
    public function ack(QueueMessageInterface $message = null)
    {
        if (!$this->currentFrame) {
            throw new \RuntimeException('You must first receive a message, before acking it');
        }

        $this->statefulStomp->ack($this->currentFrame);
        $this->currentFrame = null;
    }

    /**
     * {@inheritdoc}
     */
    public function nack(QueueMessageInterface $message = null)
    {
        if (!$this->currentFrame) {
            throw new \RuntimeException('You must first receive a message, before nacking it');
        }

        $this->statefulStomp->nack($this->currentFrame);
        $this->currentFrame = null;
    }

    public function createQueueMessage(): QueueMessageInterface
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
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this->disconnect();
    }

    /**
     * Calls the doDestroy method on console.terminate event.
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
}
