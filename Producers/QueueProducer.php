<?php

namespace Smartbox\Integration\FrameworkBundle\Producers;

use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Drivers\Queue\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Endpoints\QueueEndpoint;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Traits\UsesDriverRegistry;
use Smartbox\Integration\FrameworkBundle\Traits\UsesSerializer;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Class QueueProducer
 * @package Smartbox\Integration\FrameworkBundle\Producers
 */
class QueueProducer extends Producer
{
    use UsesSerializer;
    use UsesDriverRegistry;

    protected $headersToPropagate = array(
        Message::HEADER_EXPIRES
    );

    /**
     * {@inheritDoc}
     */
    public function send(Exchange $ex, EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        $msg = $ex->getIn();
        $queueDriverName = $options[QueueEndpoint::OPTION_QUEUE_DRIVER];
        $queueName = ($options[QueueEndpoint::OPTION_PREFIX]).$options[QueueEndpoint::OPTION_QUEUE_NAME];

        /** @var QueueDriverInterface $queueDriver */
        $queueDriver = $this->getDriverRegistry()->getDriver($queueDriverName);
        if (!$queueDriver) {
            throw new ResourceNotFoundException(
                "Queue driver $queueDriverName not found in QueueProducer while trying to send message to endpoint with URI: "
                .$endpoint->getURI()
            );
        }

        if(!($queueDriver instanceof QueueDriverInterface)){
            throw new \RuntimeException("Found queue driver with name '$queueDriverName' that doesn't implement QueueDriverInterface");
        }

        $queueMessage = $queueDriver->createQueueMessage();
        $queueMessage->setBody($msg);
        $queueMessage->setTTL($options[QueueEndpoint::OPTION_TTL]);
        $queueMessage->setQueue($queueName);
        $queueMessage->setPersistent($options[QueueEndpoint::OPTION_PERSISTENT]);
        $queueMessage->setPriority($options[QueueEndpoint::OPTION_PRIORITY]);
        $queueMessage->setDestinationURI($endpoint->getURI());

        if ($type = @$options[QueueEndpoint::OPTION_TYPE]) {
            $queueMessage->setMessageType($type);
        }

        // Take other headers from msg
        foreach ($this->headersToPropagate as $header) {
            if ($msg->getHeader($header)) {
                $queueMessage->setHeader($header, $msg->getHeader($header));
            }
        }

        // Send
        if (!$queueDriver->isConnected()) {
            $queueDriver->connect();
        }

        $success = $queueDriver->send($queueMessage);

        if (!$success) {
            throw new \RuntimeException("The message could not be delivered to the queue '$queueName' while using queue driver '$queueDriverName'");
        }
    }
}
