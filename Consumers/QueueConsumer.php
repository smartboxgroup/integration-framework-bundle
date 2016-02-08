<?php

namespace Smartbox\Integration\FrameworkBundle\Consumers;

use Smartbox\Integration\FrameworkBundle\Messages\Queues\QueueMessageInterface;

/**
 * Class QueueConsumer
 * @package Smartbox\Integration\FrameworkBundle\Consumers
 */
class QueueConsumer extends AbstractQueueConsumer implements QueueConsumerInterface, UsesMessageHandlerInterface
{
    use UsesMessageHandler;

    /**
     * {@inheritDoc}
     */
    protected function process(QueueMessageInterface $message)
    {
        $this->getHandler()->handle($message->getBody(),$message->getDestinationURI());
    }
}
