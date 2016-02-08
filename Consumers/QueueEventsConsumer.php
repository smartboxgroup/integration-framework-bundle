<?php

namespace Smartbox\Integration\FrameworkBundle\Consumers;

use Smartbox\Integration\FrameworkBundle\Messages\Queues\QueueMessageInterface;


/**
 * Class QueueConsumer
 * @package Smartbox\Integration\FrameworkBundle\Consumers
 */
class QueueEventsConsumer extends AbstractQueueConsumer implements QueueConsumerInterface, UsesDeferredEventsHandlerInterface
{
    use UsesDeferredEventsHandler;

    /**
     * {@inheritDoc}
     */
    protected function process(QueueMessageInterface $message)
    {
        $this->getHandler()->handle($message->getBody());
    }
}
