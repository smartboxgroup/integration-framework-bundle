<?php

namespace Smartbox\Integration\FrameworkBundle\Components\FileService\Csv;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Components\DB\ConfigurableStepsProviderInterface;
use Smartbox\Integration\FrameworkBundle\Configurability\IsConfigurableService;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConfigurableConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\Exceptions\NoResultsException;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\IsStopableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractConsumer;

class CsvConfigurableConsumer extends AbstractConsumer implements ConfigurableConsumerInterface
{
    use IsStopableConsumer;
    use UsesSmartesbHelper;
    use IsConfigurableService;

    /** @var  ConfigurableStepsProviderInterface */
    protected $configurableStepsProvider;

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
     * {@inheritdoc}
     */
    protected function initialize(EndpointInterface $endpoint)
    {
    }

    /**
     * Reads a message from the CSV file and executing the configured steps
     *
     * @param EndpointInterface $endpoint
     * @return \Smartbox\Integration\FrameworkBundle\Core\Messages\Message
     */
    protected function readMessage(EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        $method = $options[CsvConfigurableProtocol::OPTION_METHOD];
        $config = $this->methodsConfiguration[$method];
        $steps = $config[ConfigurableConsumerInterface::CONFIG_QUERY_STEPS];

        $context = $this->getConfHelper()->createContext($options);

        try {
            $this->configurableStepsProvider->executeSteps($steps, $options, $context);

            $result = $this->getConfHelper()->resolve(
                $config[ConfigurableConsumerInterface::CONFIG_QUERY_RESULT],
                $context
            );
        } catch(NoResultsException $exception) { // TODO: Replace with a actual end of file exception
            $result = null;
            if ( $options[CsvConfigurableProtocol::OPTION_STOP_ON_EOF] ){
                $this->stop();
            }
        }

        if($result == null ){
            return null;
        }elseif(is_array($result)){
            $result = new SerializableArray($result);
        }

        return $this->smartesbHelper->getMessageFactory()->createMessage($result);
    }

    /**
     * {@inheritdoc}
     */
    protected function cleanUp(EndpointInterface $endpoint)
    {
        $empty = [];
        $options = $endpoint->getOptions();
        $this->getConfigurableStepsProvider()->executeStep(CsvConfigurableStepsProvider::STEP_CLEAN_FILE_HANDLES, $empty, $options, $empty);
    }

    /**
     * {@inheritdoc}
     */
    protected function confirmMessage(EndpointInterface $endpoint, MessageInterface $message)
    {
        return $message;
    }
}