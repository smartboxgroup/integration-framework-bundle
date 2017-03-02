<?php

namespace Smartbox\Integration\FrameworkBundle\Components\FileService\Csv;


use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\NoSQLConfigurableProtocol;
use Smartbox\Integration\FrameworkBundle\Configurability\IsConfigurableService;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConfigurableConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\Exceptions\NoResultsException;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\IsStopableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractConsumer;


class CsvConfigurableConsumer extends AbstractConsumer implements ConfigurableConsumerInterface
{
    use IsStopableConsumer;
    use UsesSmartesbHelper;
    use IsConfigurableService;

    /** @var  ConfigurableStepsProviderInterface */
    protected $configurableStepsProvider;

    protected $fileHandle;

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
        $options = $endpoint->getOptions();

        // Open file
        $rootPath = $this->configurableStepsProvider->getRootPath($options);
        $filePath = $options[CsvConfigurableStepsProvider::PARAM_FILE_PATH];
        $fullPath = $rootPath . DIRECTORY_SEPARATOR . $filePath;

        $this->fileHandle = fopen( $fullPath, 'r' );
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

        $method = $options[SymfonyServiceProtocol::OPTION_METHOD];
        $config = $this->methodsConfiguration[$method];
        $steps = $config[ConfigurableProducerInterface::CONF_STEPS];

        $context = $this->getConfHelper()->createContext($options, $exchange->getIn(), $exchange);
        $context[ConfigurableStepsProviderInterface::CONTEXT_FILE_HANDLE] = $this->file_handle;

        try{
            $this->configurableStepsProvider->executeSteps($steps, $options, $context);

            $result = $this->getConfHelper()->resolve(
                $config[ConfigurableConsumerInterface::CONFIG_QUERY_RESULT],
                $context
            );
        }catch(NoResultsException $exception){
            $result = null;
        }

        if($result == null){
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
        fclose($this->fileHandle);
    }

}