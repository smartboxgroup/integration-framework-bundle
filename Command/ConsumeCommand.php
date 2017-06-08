<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->setHelp("Run the consumer. You can kill the consumer after x messages by using the --killAfter option.
Ex:
Consume all the messages, and never die:
app/console smartesb:consumer:start queue://main/api --killAfter -1

Consume the events and die after 10 messages:
app/console smartesb:consumer:start queue://events --killAfter 10

Recover on eai_test database all transactions corresponding to a string containing xxxxxx (using %...%) on mediapost (double quotes are mandatory if you don't want to have to url encode the uri):
app/console smartesb:consumer:start \"dbal://eai/recover?searchString=%xxxxxxx%&databaseName=eai_test&targetUri=%//mediapost/%\"

Discard on the default database all transactions corresponding exactly to the string \"Error\" :
app/console smartesb:consumer:start \"dbal://eai/discard?searchString=Error\"

Discard on the default database all the transactions for the  method getWebOrderDetailsByCriteria:
app/console smartesb:consumer:start \"dbal://eai/discard?targetUri=%getWebOrderDetailsByCriteria%\"
")
        ;

        $this->addArgument(
            'uri',
            InputArgument::REQUIRED,
            'Source URI ( e.g.: queue://api/*/*/*/* )'
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
        $producer = null;

        $this->endpoint = $this->getSourceEndpoint();
        $message = '<info>Consuming from '.$this->endpoint->getURI();
        if ($input->getOption(self::OPTION_MAX_MESSAGES) > 0) {
            $message .= ' limited to '.$input->getOption(self::OPTION_MAX_MESSAGES).' messages';
        }
        $message .= '.</info>';
        $output->writeln($message);

        pcntl_signal(SIGINT, [$this, 'handleSignal']);
        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        $this->endpoint->consume($input->getOption(self::OPTION_MAX_MESSAGES));

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
