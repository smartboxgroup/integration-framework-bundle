<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

use Smartbox\Integration\FrameworkBundle\DependencyInjection\SmartboxIntegrationFrameworkExtension;
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
        return 'smartbox:consumers:start';
    }

    /**
     * {@inheritDoc}
     */
    protected function getConsumer()
    {
        $consumerName = $this->getInput()->getArgument('consumer');
        return $this->getContainer()->get(SmartboxIntegrationFrameworkExtension::CONSUMER_PREFIX.$consumerName);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueueName()
    {
        return $this->getInput()->getArgument('source');
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->addArgument(
            'consumer',
            InputArgument::REQUIRED,
            'Consumer name'
        );

        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            'Source name (E.g.: queue name)'
        );
    }
}
