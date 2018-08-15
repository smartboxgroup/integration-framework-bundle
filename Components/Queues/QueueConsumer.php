<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Events\TimingEvent;

/**
 * Class QueueConsumer.
 */
class QueueConsumer extends AbstractConsumer implements ConsumerInterface
{

    /**
     * @var int The time it took in ms to deserialize the message
     */
    protected $deQueueingTimeMs = 0;

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
     * @return \Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface
     */
    protected function getQueueDriver(EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        $queueDriverName = $options[QueueProtocol::OPTION_QUEUE_DRIVER];
        $queueDriver = $this->smartesbHelper->getQueueDriver($queueDriverName);

        if ($queueDriver instanceof QueueDriverInterface) {
            return $queueDriver;
        }

        throw new \RuntimeException("Error in QueueConsumer, the driver with name $queueDriverName does not implement the interface QueueDriverInterface");
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
        $this->deQueueingTimeMs = $driver->getDeQueueingTimeMs();

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
     * @inheritdoc
     *
     * @param $intervalMs int the timing interval that we would like to emanate
     * @return mixed
     */
    protected function dispatchConsumerTimingEvent($intervalMs, MessageInterface $message)
    {
        $intervalMs = $intervalMs + $this->deQueueingTimeMs;

        parent::dispatchConsumerTimingEvent($intervalMs, $message);
    }
}
