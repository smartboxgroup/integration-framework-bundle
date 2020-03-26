<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\SyncQueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

/**
 * Class QueueConsumer.
 */
class QueueConsumer extends AbstractConsumer
{
    /**
     * @var int The time it took in ms to deserialize the message
     */
    protected $dequeueingTimeMs = 0;

    /**
     * {@inheritdoc}
     */
    protected function initialize(EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        $queuePrefix = $options[QueueProtocol::OPTION_PREFIX];
        $queueName = $options[QueueProtocol::OPTION_QUEUE_NAME];
        $queuePath = $queuePrefix.$queueName;

        $driver = $this->getQueueDriver($endpoint);
        $driver->connect();
        $driver->subscribe($queuePath);
    }

    /**
     * @param EndpointInterface $endpoint
     *
     * @return SyncQueueDriverInterface
     */
    protected function getQueueDriver(EndpointInterface $endpoint): SyncQueueDriverInterface
    {
        $options = $endpoint->getOptions();
        $queueDriverName = $options[QueueProtocol::OPTION_QUEUE_DRIVER];

        return $this->smartesbHelper->getQueueDriver($queueDriverName);
    }

    /**
     * {@inheritdoc}
     */
    protected function cleanUp(EndpointInterface $endpoint)
    {
        $driver = $this->getQueueDriver($endpoint);
        $driver->unSubscribe();
    }

    /**
     * {@inheritdoc}
     */
    protected function readMessage(EndpointInterface $endpoint)
    {
        $driver = $this->getQueueDriver($endpoint);
        $this->dequeueingTimeMs = $driver->getDequeueingTimeMs();

        return $driver->receive();
    }

    /**
     * {@inheritdoc}
     */
    protected function process(EndpointInterface $queueEndpoint, MessageInterface $message)
    {
        // If we used a wrapper to queue the message, that the handler doesn't understand, unwrap it
        if ($message instanceof QueueMessageInterface && !($queueEndpoint->getHandler() instanceof QueueMessageHandlerInterface)) {
            $endpoint = $this->smartesbHelper->getEndpointFactory()->createEndpoint($message->getDestinationURI(), EndpointFactory::MODE_CONSUME);
            $queueEndpoint->getHandler()->handle($message->getBody(), $endpoint);
        } else {
            parent::process($queueEndpoint, $message);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function confirmMessage(EndpointInterface $endpoint, MessageInterface $message)
    {
        $driver = $this->getQueueDriver($endpoint);
        $driver->ack();
    }

    /**
     * {@inheritdoc}
     *
     * @param $intervalMs int the timing interval that we would like to emanate
     *
     * @return mixed
     */
    protected function dispatchConsumerTimingEvent($intervalMs, MessageInterface $message)
    {
        $intervalMs += $this->dequeueingTimeMs;

        parent::dispatchConsumerTimingEvent($intervalMs, $message);
    }
}
