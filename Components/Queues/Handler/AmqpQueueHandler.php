<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Handler;

use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageHandlerInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;

class AmqpQueueHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use UsesSmartesbHelper;

    /**
     * @var EndpointInterface
     */
    private $endpoint;

    /**
     * @var int
     */
    private $max;

    /**
     * @var string
     */
    private $format;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    private $stopped = false;

    /**
     * AmqpQueueHandler constructor.
     *
     * @param EndpointInterface        $endpoint   The endpoint used to consume
     * @param int                      $max        Maximum amount of message to consume before stopping
     * @param string                   $format     json|xml|php
     * @param SerializerInterface|null $serializer Serializer to use for deserialization
     */
    public function __construct(EndpointInterface $endpoint, int $max = -1, string $format = 'json', SerializerInterface $serializer = null)
    {
        $this->endpoint = $endpoint;
        $this->max = $max;
        $this->format = $format;
        $this->serializer = $serializer ?? SerializerBuilder::create()->build();
    }

    public function __invoke(\AMQPEnvelope $envelope, \AMQPQueue $queue)
    {
        if ($this->shouldStop()) {
            $queue->nack($envelope->getDeliveryTag(), AMQP_REQUEUE);
            $this->log('Handler stopped on {time}');

            return false;
        }

        $this->log('A message was received on {time}');

        /** @var \Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface|QueueMessageInterface $message */
        $message = $this->serializer->deserialize($envelope->getBody(), SerializableInterface::class, $this->format);

        if ($this->isQueueMessage($message) && null !== $this->smartesbHelper) {
            $endpoint = $this->smartesbHelper->getEndpointFactory()->createEndpoint($message->getDestinationURI(), EndpointFactory::MODE_CONSUME);
            $endpoint->getHandler()->handle($message->getBody(), $endpoint);
        } else {
            $this->endpoint->handle($message);
        }

        $queue->ack($envelope->getDeliveryTag());
        $this->log('A message was consumed on {time}');
        --$this->max;

        if ($this->shouldStop()) {
            $this->log('Handler stopped on {time}');

            return false;
        }
    }

    /**
     * @return bool
     */
    public function isStopped(): bool
    {
        return $this->stopped;
    }

    public function stop()
    {
        $this->stopped = true;
    }

    private function shouldStop(): bool
    {
        if (0 === $this->max) {
            $this->stopped = true;
        }

        return $this->isStopped();
    }

    /**
     * @param \Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface $message
     *
     * @return bool
     */
    private function isQueueMessage($message): bool
    {
        return $message instanceof QueueMessageInterface && !($this->endpoint->getHandler() instanceof QueueMessageHandlerInterface);
    }

    private function log(string $message, array $ctx = [])
    {
        if (null === $this->logger) {
            return;
        }

        $now = new \DateTime();
        $ctx['time'] = $now->format('Y-m-d H:i:s.u');
        $this->logger->info($message, $ctx);
    }
}
