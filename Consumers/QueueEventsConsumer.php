<?php

namespace Smartbox\Integration\FrameworkBundle\Consumers;

use Smartbox\Integration\FrameworkBundle\Messages\Queues\QueueMessage;


/**
 * Class QueueConsumer
 * @package Smartbox\Integration\FrameworkBundle\Consumers
 */
class QueueEventsConsumer extends AbstractQueueConsumer implements QueueConsumerInterface, UsesDeferredEventsHandlerInterface
{
    use UsesDeferredEventsHandler;

    protected function process(QueueMessage $message)
    {
        $this->getHandler()->handle($message->getBody());
    }
}
