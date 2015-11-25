<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

/**
 * Class EventsQueueConsumerCommand
 * @package Smartbox\Integration\FrameworkBundle\Command
 */
class EventsQueueConsumerCommand extends QueueConsumerCommand
{
    /**
     * {@inheritDoc}
     */
    protected function getCommandName()
    {
        return 'smartbox:consumers:queue:events:start';
    }

    /**
     * {@inheritDoc}
     */
    protected function getConsumer()
    {
        return $this->getContainer()->get('smartbox.consumers.queue.events');
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueueName()
    {
        return $this->getContainer()->getParameter('smartesb.events_queue_name');
    }
}
