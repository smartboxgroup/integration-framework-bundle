<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\SyncQueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Serializers\UsesQueueSerializer;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;

/**
 * Class QueueConsumer.
 */
class QueueConsumer extends AbstractConsumer
{
    use UsesExceptionHandlerTrait;
    use UsesQueueSerializer;

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
        $encodedMessage = $driver->receive();

        if (!$encodedMessage) {
            return null;
        }
        
        try {
            $start = microtime(true);
            $message = $this->getSerializer()->decode([
                'body' => $encodedMessage->getBody(),
                'headers' => $encodedMessage->getHeaders()
            ]);

            $this->consumptionDuration += (microtime(true) - $start) * 1000;
        } catch (\Exception $exception) {
            $this->getExceptionHandler()($exception, $endpoint, ['body' => $encodedMessage->getBody(), 'headers' => $encodedMessage->getHeaders()]);
            $driver->ack();

            $this->consumptionDuration += (microtime(true) - $start) * 1000;

            return null;
        }

        return $message;
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
}
