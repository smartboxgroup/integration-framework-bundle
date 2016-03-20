<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesDriverRegistry;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Class QueueProducer
 * @package Smartbox\Integration\FrameworkBundle\Core\Producers
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
        $queueDriverName = $options[QueueProtocol::OPTION_QUEUE_DRIVER];
        $queueName = ($options[QueueProtocol::OPTION_PREFIX]).$options[QueueProtocol::OPTION_QUEUE_NAME];

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
