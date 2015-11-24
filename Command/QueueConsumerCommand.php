<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

use Smartbox\Integration\FrameworkBundle\Consumers\QueueConsumer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class QueueConsumerCommand
 * @package Smartbox\Integration\FrameworkBundle\Command
 */
abstract class QueueConsumerCommand extends ContainerAwareCommand
{
    /** @var QueueConsumer */
    protected $consumer;

    /** @var InputInterface */
    protected $input;

    /**
     * @return string
     */
    abstract protected function getCommandName();

    /**
     * @return QueueConsumer
     */
    abstract protected function getConsumer();

    /**
     * @return string
     */
    abstract protected function getQueueName();

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName($this->getCommandName())
            ->setDescription('Start a worker consuming messages from a given connector')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $connector = null;

        declare(ticks = 1);
        pcntl_signal(SIGINT, [$this, 'handleSignal']);
        pcntl_signal(SIGTERM, [$this, 'handleSignal']);

        $this->consumer = $this->getConsumer();

        $output->writeln('<info>Consuming from ' . $this->getQueueName() . '.</info>');

        $this->consumer->consume($this->getQueueName());

        $output->writeln('<info>Queue consumer was gracefully stopped.</info>');
    }

    /**
     * @return InputInterface
     */
    protected function getInput()
    {
        return $this->input;
    }

    /**
     * Handles a signal
     */
    public function handleSignal()
    {
        $this->consumer->stop();
    }
}
