<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ConsumeCommand.
 */
class ConsumeCommand extends ContainerAwareCommand
{
    /** @var EndpointInterface */
    protected $endpoint;

    /** @var InputInterface */
    protected $input;

    /**
     * @return \Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface
     */
    protected function getSourceEndpoint()
    {
        $uri = $this->getInput()->getArgument('uri');

        return $this->getContainer()->get('smartesb.endpoint_factory')->createEndpoint($uri);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('smartesb:consumer:start')
            ->setDescription('Start a daemon consuming messages from a given URI')
        ;

        $this->addArgument(
            'uri',
            InputArgument::REQUIRED,
            'Source URI ( e.g.: queue://api/*/*/*/* )'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $producer = null;

        $this->endpoint = $this->getSourceEndpoint();

        $output->writeln('<info>Consuming from '.$this->endpoint->getURI().'.</info>');

        declare(ticks=10);
        pcntl_signal(SIGINT, [$this, 'handleSignal']);
        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        $this->endpoint->consume();

        $output->writeln('<info>Consumer was gracefully stopped for: '.$this->endpoint->getURI().'</info>');
    }

    /**
     * @return InputInterface
     */
    protected function getInput()
    {
        return $this->input;
    }

    /**
     * Handles a signal.
     */
    public function handleSignal()
    {
        $this->endpoint->getConsumer()->stop();
    }
}
