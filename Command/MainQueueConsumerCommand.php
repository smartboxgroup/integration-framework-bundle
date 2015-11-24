<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

use Symfony\Component\Console\Input\InputArgument;

/**
 * Class MainQueueConsumerCommand
 * @package Smartbox\Integration\FrameworkBundle\Command
 */
class MainQueueConsumerCommand extends QueueConsumerCommand
{
    /**
     * {@inheritDoc}
     */
    protected function getCommandName()
    {
        return 'smartbox:consumers:queue:main:start';
    }

    /**
     * {@inheritDoc}
     */
    protected function getConsumer()
    {
        return $this->getContainer()->get('smartbox.consumers.queue.main');
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueueName()
    {
        return $this->getInput()->getArgument('queue');
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->addArgument(
            'queue',
            InputArgument::REQUIRED,
            'Queue name'
        );
    }
}
