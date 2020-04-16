<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\SyncQueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesDecodingExceptionHandler;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesQueueSerializer;

/**
 * Class QueueConsumer.
 */
class QueueConsumer extends AbstractConsumer
{
    use UsesDecodingExceptionHandler;
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

        if (null === $encodedMessage) {
            return null;
        }

        $body = $encodedMessage->getBody();
        $headers = $encodedMessage->getHeaders();

        try {
            $start = microtime(true);
            $message = $this->getSerializer()->decode([
                'body' => $body,
                'headers' => $headers,
            ]);
        } catch (\Exception $exception) {
            $message = $this->getDecodingExceptionHandler()->handle($exception, [
                'endpoint' => $endpoint,
                'body' => $body,
                'headers' => $headers,
            ]);

            // If the exception handler doesn't return a new message, consider it poisoned and discard it.
            if (null === $message) {
                $driver->ack();

                $this->consumptionDuration += (int) ((microtime(true) - $start) * 1000);

                return null;
            }
        }

        $this->consumptionDuration += (int) ((microtime(true) - $start) * 1000);

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
