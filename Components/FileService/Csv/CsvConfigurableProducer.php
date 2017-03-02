<?php

namespace Smartbox\Integration\FrameworkBundle\Components\FileService\Csv;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Producers\AbstractConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Exceptions\Deprecated\InvalidFormatException;
use Smartbox\Integration\FrameworkBundle\Components\FileService\Csv\CsvConfigurableProtocol;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Smartbox\Integration\FrameworkBundle\Components\SymfonyService\SymfonyServiceProtocol;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ConfigurableProducerInterface;

/**
 * Class CsvConfigurableProducer.
 */
class CsvConfigurableProducer extends AbstractConfigurableProducer implements ConfigurableProducerInterface
{

    use UsesSerializer;

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
    public function send(Exchange $exchange, EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();

        // Open file
        $root_path = $this->configurableStepsProvider->getRootPath($options);
        $file_path = $options[CsvConfigurableStepsProvider::PARAM_FILE_PATH];
        $file_handle = fopen( $root_path . DIRECTORY_SEPARATOR . $file_path , 'w' );

        $method = $options[SymfonyServiceProtocol::OPTION_METHOD];
        $config = $this->methodsConfiguration[$method];
        $steps = $config[ConfigurableProducerInterface::CONF_STEPS];

        $context = $this->getConfHelper()->createContext($options, $exchange->getIn(), $exchange);
        $context[ConfigurableStepsProviderInterface::CONTEXT_FILE_HANDLE] = $file_handle;

        $this->configurableStepsProvider->executeSteps($steps, $options, $context);

        // Close file
        fclose($file_handle);
    }
}