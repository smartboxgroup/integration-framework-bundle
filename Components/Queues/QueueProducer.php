<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesDriverRegistry;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesQueueSerializer;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Class QueueProducer.
 */
class QueueProducer extends Producer
{
    use UsesDriverRegistry;
    use UsesQueueSerializer;

    protected $headersToPropagate = [
        Message::HEADER_EXPIRES,
    ];

    /**
     * {@inheritdoc}
     */
    public function send(Exchange $ex, EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        $inMessage = $ex->getIn();
        $queueDriverName = $options[QueueProtocol::OPTION_QUEUE_DRIVER];
        $queueName = ($options[QueueProtocol::OPTION_PREFIX]).$options[QueueProtocol::OPTION_QUEUE_NAME];

        /** @var QueueDriverInterface $queueDriver */
        $queueDriver = $this->getDriverRegistry()->getDriver($queueDriverName);
        if (!$queueDriver) {
            throw new ResourceNotFoundException("Queue driver $queueDriverName not found in QueueProducer while trying to send message to endpoint with URI: ".$endpoint->getURI());
        }

        if (!($queueDriver instanceof QueueDriverInterface)) {
            throw new \RuntimeException("Found queue driver with name '$queueDriverName' that doesn't implement QueueDriverInterface");
        }

        $queueMessage = $this->createQueueMessage();
        $queueMessage->setBody($inMessage);
        $queueMessage->setTTL($options[QueueProtocol::OPTION_TTL]);
        $queueMessage->setQueue($queueName);
        $queueMessage->setPersistent($options[QueueProtocol::OPTION_PERSISTENT]);
        $queueMessage->setPriority($options[QueueProtocol::OPTION_PRIORITY]);
        $queueMessage->setDestinationURI($endpoint->getURI());

        if ($type = @$options[QueueProtocol::OPTION_TYPE]) {
            $queueMessage->setMessageType($type);
        }

        // Take other headers from msg
        foreach ($this->headersToPropagate as $header) {
            if ($inMessage->getHeader($header)) {
                $queueMessage->setHeader($header, $inMessage->getHeader($header));
            }
        }

        // Call the preSend hook
        $this->beforeSend($queueMessage, $options);

        // Send
        $queueDriver->connect();

        $encodedMessage = $this->getSerializer()->encode($queueMessage);

        $success = $queueDriver->send($queueMessage->getQueue(), $encodedMessage['body'], $encodedMessage['headers']);

        if (!$success) {
            throw new \RuntimeException("The message could not be delivered to the queue '$queueName' while using queue driver '$queueDriverName'");
        }
    }

    /**
     * A hook to allow for modifying the queue message before we send it.
     *
     * @param array $options The options set for this endpoint
     */
    protected function beforeSend(QueueMessage $queueMessage, $options)
    {
    }

    /**
     * Creates a brand new queue message with empty context.
     */
    protected function createQueueMessage(): QueueMessageInterface
    {
        $queueMessage = new QueueMessage();
        $queueMessage->setContext(new Context());

        return $queueMessage;
    }
}
