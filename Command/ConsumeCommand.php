<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

use Smartbox\CoreBundle\Utils\Helper\DateTimeCreator;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;

/**
 * Class ConsumeCommand.
 */
class ConsumeCommand extends ContainerAwareCommand
{
    const OPTION_MAX_MESSAGES = 'killAfter';
    const OPTION_MAX_MESSAGES_DEFAULT_VALUE = -1; // -1 = No limit

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

        return $this->getContainer()->get('smartesb.endpoint_factory')->createEndpoint($uri, EndpointFactory::MODE_CONSUME);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('smartesb:consumer:start')
            ->setDescription('Start a daemon consuming messages from a given URI')
            ->setHelp('Run the consumer. You can kill the consumer after x messages by using the --killAfter option. Use the -vv option to display extra information.
Ex:
Consume all the messages, never die and display an alert each time a message is consumed:
app/console smartesb:consumer:start queue://api -vv --killAfter -1

Consume the events and die after 10 messages:
app/console smartesb:consumer:start queue://events --killAfter 10
')
        ;

        $this->addArgument(
            'uri',
            InputArgument::REQUIRED,
            'Source URI ( e.g.: queue://api/* )'
        );
        $this->addOption(
            self::OPTION_MAX_MESSAGES,
            'k',
            InputOption::VALUE_REQUIRED,
            'How many messages should be processed before the worker is killed? -1 for never, default value is '.self::OPTION_MAX_MESSAGES_DEFAULT_VALUE.'.',
            self::OPTION_MAX_MESSAGES_DEFAULT_VALUE
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;

        $this->endpoint = $this->getSourceEndpoint();

        $consumer = $this->endpoint->getConsumer();
        if (method_exists($consumer, 'setLogger')) {
            $logger = new ConsoleLogger($output);
            $consumer->setLogger($logger);
        }

        $now = DateTimeCreator::getNowDateTime();
        $message = '<info>'.$now->format('Y-m-d H:i:s.u').' Consuming from '.$this->endpoint->getURI();
        if ($input->getOption(self::OPTION_MAX_MESSAGES) > 0) {
            $message .= ' limited to '.$input->getOption(self::OPTION_MAX_MESSAGES).' messages';
        }
        $message .= '.</info>';
        $output->writeln($message);

        pcntl_signal(SIGINT, [$this, 'handleSignal']);
        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        pcntl_signal(SIGUSR2, [$this, 'handleSignal']);

        $this->endpoint->consume($input->getOption(self::OPTION_MAX_MESSAGES));

        $now = DateTimeCreator::getNowDateTime();
        $output->writeln('<info>'.$now->format('Y-m-d H:i:s.u').' Consumer was gracefully stopped for '.$this->endpoint->getURI().'</info>');
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
