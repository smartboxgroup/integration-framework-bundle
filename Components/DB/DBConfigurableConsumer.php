<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Components\DB\Dbal\ConfigurableDbalProtocol;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\NoSQLConfigurableProtocol;
use Smartbox\Integration\FrameworkBundle\Configurability\IsConfigurableService;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConfigurableConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\Exceptions\NoResultsException;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\IsStopableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesLogger;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Service;

class DBConfigurableConsumer extends AbstractConsumer implements ConfigurableConsumerInterface
{
    use IsConfigurableService;

    /** @var ConfigurableStepsProviderInterface */
    protected $configurableStepsProvider;

    /**
     * {@inheritdoc}
     */
    protected function initialize(EndpointInterface $endpoint)
    {
    }

    /**
     * @return ConfigurableStepsProviderInterface
     */
    public function getConfigurableStepsProvider()
    {
        return $this->configurableStepsProvider;
    }

    /**
     * @param ConfigurableStepsProviderInterface $configurableStepsProvider
     */
    public function setConfigurableStepsProvider($configurableStepsProvider)
    {
        $this->configurableStepsProvider = $configurableStepsProvider;
    }

    /**
     * Reads a message from the NoSQL database executing the configured steps.
     *
     * @param EndpointInterface $endpoint
     *
     * @return \Smartbox\Integration\FrameworkBundle\Core\Messages\Message
     */
    protected function readMessage(EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        $method = $options[NoSQLConfigurableProtocol::OPTION_METHOD];
        $config = $this->methodsConfiguration[$method];
        $steps = $config[ConfigurableConsumerInterface::CONFIG_QUERY_STEPS];

        $context = $this->getConfHelper()->createContext($options);

        try {
            $this->configurableStepsProvider->executeSteps($steps, $options, $context);

            $result = $this->getConfHelper()->resolve(
                $config[ConfigurableConsumerInterface::CONFIG_QUERY_RESULT],
                $context
            );
        } catch (NoResultsException $exception) {
            $result = null;
            if ($options[ConfigurableDbalProtocol::OPTION_STOP_ON_NO_RESULTS]) {
                $this->stop();
            }
        }

        if (null == $result) {
            return null;
        } elseif (is_array($result)) {
            $result = new SerializableArray($result);
        }

        $context = new Context([
            Context::FLOWS_VERSION => $this->getFlowsVersion(),
            Context::TRANSACTION_ID => uniqid('', true),
            Context::ORIGINAL_FROM => $endpoint->getURI(),
        ]);

        return $this->smartesbHelper->getMessageFactory()->createMessage($result, [], $context);
    }

    /**
     * Executes the necessary actions after the message has been consumed.
     *
     * @param EndpointInterface $endpoint
     * @param MessageInterface  $message
     */
    protected function onConsume(EndpointInterface $endpoint, MessageInterface $message)
    {
        $options = $endpoint->getOptions();
        $method = $options[NoSQLConfigurableProtocol::OPTION_METHOD];
        $config = $this->methodsConfiguration[$method];
        $steps = $config[ConfigurableConsumerInterface::CONFIG_ON_CONSUME];

        $context = $this->getConfHelper()->createContext($options, $message);

        $this->configurableStepsProvider->executeSteps($steps, $options, $context);
    }

    /**
     * @param EndpointInterface $endpoint
     */
    public function consume(EndpointInterface $endpoint)
    {
        $sleepTime = (int) $endpoint->getOption(ConfigurableDbalProtocol::OPTION_SLEEP_TIME) * 1000;
        $inactivityTrigger = (int) $endpoint->getOption(ConfigurableDbalProtocol::OPTION_INACTIVITY_TRIGGER);
        $wakeup = microtime(true);

        while (!$this->shouldStop()) {
            // Receive
            $startConsumeTime = microtime(true);
            $message = $this->readMessage($endpoint);

            // Process
            if ($message) {
                --$this->expirationCount;

                $endpoint->handle($message);

                if ($this->logger) {
                    $microTime = number_format(microtime(true), 6, '.', '');
                    $now = \DateTime::createFromFormat('U.u', $microTime);

                    $this->logger->info(
                        'A message was consumed on {date}',
                        ['date' => \DateTime::createFromFormat('U.u', $now->format('Y-m-d H:i:s.u')]
                    );
                }

                $this->confirmMessage($endpoint, $message);
                $endConsumeTime = $wakeup = microtime(true);
                $this->dispatchConsumerTimingEvent((int) (($endConsumeTime - $startConsumeTime) * 1000), $message);
            }

            if ((microtime(true) - $wakeup) > $inactivityTrigger) { // I did nothing since the last x seconds, so little nap...
                usleep($sleepTime);
            }
        }

        $this->cleanUp($endpoint);
    }

    /**
     * {@inheritdoc}
     */
    protected function cleanUp(EndpointInterface $endpoint)
    {
        //TODO: Connect to the steps provider and ask it to shut down its connection
    }

    /**
     * {@inheritdoc}
     */
    protected function confirmMessage(EndpointInterface $endpoint, MessageInterface $message)
    {
        $this->onConsume($endpoint, $message);
        return $message;
    }
}
