<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Handler;

use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageHandlerInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\IsStopableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;

/**
 * Class PhpAmqpHandler
 * @package Smartbox\Integration\FrameworkBundle\Components\Queues\Handler
 */
class PhpAmqpHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use UsesSmartesbHelper;
    use UsesExceptionHandlerTrait;
    use IsStopableConsumer;

    /**
     * @var EndpointInterface
     */
    private $endpoint;

    /**
     * @var string
     */
    private $format;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @param EndpointInterface        $endpoint   The endpoint used to consume
     * @param int                      $expirationCount  Maximum amount of message to consume before stopping
     * @param string                   $format     json|xml|php
     * @param null $serializer Serializer to use for deserialization
     */
    public function __construct(EndpointInterface $endpoint, int $expirationCount = -1, string $format = QueueDriverInterface::FORMAT_JSON, $serializer = null)
    {
        $this->endpoint = $endpoint;
        $this->setExpirationCount($expirationCount);
        $this->format = $format;
        $this->serializer = $serializer ?? SerializerBuilder::create()->build();
    }



    /**
     * Verifies if the channel is consuming a message
     * If there is a message to consume it calls the consume callback function
     * If there is no message to consume it will put the worker in a wait state
     *
     * @param AMQPChannel $channel
     * @throws \Exception
     */
    private function isConsuming(AMQPChannel $channel)
    {
        try {
            while ($channel->is_consuming()) {
                $channel->wait(null, true);
            }
        } catch (\Exception $exception) {
            $this->log($exception->getMessage());
            return;
        }
    }

    /**
     * Set the flag to stop the worker as true
     * Dispatch the command to stop the work on next iteration
     *
     * @param string $consumerTag
     * @throws \Exception
     */
    public function stopConsume(string $consumerTag)
    {
        try {
            $this->stop();
            if ($this->channel && $this->channel->getConnection()) {
                $this->channel->basic_cancel($consumerTag, true, true);
            }
        } catch (\Exception $exception) {
            $this->log($exception->getMessage());
            exit(1);
        }
    }

    /**
     * Verifies if the message object is an instance of QueueMessageInterface
     * and if the handler is not an instance of QueueMessageHandlerInterface. Used by dispatchMessage function
     *
     * @param MessageInterface $message
     * @return bool
     */
    private function isQueueMessage($message): bool
    {
        return $message instanceof QueueMessageInterface && !($this->endpoint->getHandler() instanceof QueueMessageHandlerInterface);
    }

    /**
     * Log in console the message passed as param
     *
     * @param string $message
     * @param array $ctx
     * @throws \Exception
     */
    private function log(string $message, array $ctx = [])
    {
        if (null === $this->logger) {
            return;
        }

        $now = new \DateTime();
        $ctx['time'] = $now->format('Y-m-d H:i:s.u');
        $this->logger->info($message, $ctx);
    }

    /**
     * Dispatch the message to target system or events to database
     *
     * @param QueueMessage $message
     * @throws \Exception
     */
    private function dispatchMessage(QueueMessage $message)
    {
        if (!$this->endpoint) {
            $this->getExceptionHandler()('Endpoint is undefined');
            return;
        }

        try {
            if ($this->isQueueMessage($message) && null !== $this->smartesbHelper) {
                $endpoint = $this->smartesbHelper->getEndpointFactory()->createEndpoint($message->getDestinationURI(), EndpointFactory::MODE_CONSUME);
                $endpoint->getHandler()->handle($message->getBody(), $endpoint);
            } else {
                $this->endpoint->handle($message);
            }
        } catch (\Exception $exception) {
            $this->getExceptionHandler()($exception, ['headers' => $message->getHeaders(), 'body' => $message->getBody()]);
        }
    }

    /**
     * Deserialize the messages and dispatch them to the other targets
     *
     * @param AMQPMessage
     * @return  MessageInterface|QueueMessageInterface $message
     */
    public function deserializeMessage(AMQPMessage $message): QueueMessageInterface
    {
        try {
            return $this->serializer->deserialize($message->getBody(), SerializableInterface::class, $this->format);
        } catch (\Exception $exception) {
            $this->getExceptionHandler()($exception, ['headers' => $message->getHeaders(), 'body' => $message->getBody()]);
        }
    }

    /**
     * Set a AMQPChannel object to class variable
     *
     * @param AMQPChannel $channel
     */
    public function setChannel(AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * Verifies if the channel is open and connected
     *
     * @param AMQPChannel $channel
     * @return bool
     */
    public function isConnected(AMQPChannel $channel): bool
    {
        if (!$channel->is_open()){
            $this->getExceptionHandler()('Fail in connection when trying to consume the messages');
        }
        return true;
    }
}