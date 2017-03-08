<?php

namespace Smartbox\Integration\FrameworkBundle\Components\FileService\Csv;

use Smartbox\Integration\FrameworkBundle\Components\DB\ConfigurableStepsProviderInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Producers\AbstractConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
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

        $method = $options[CsvConfigurableProtocol::OPTION_METHOD];
        $config = $this->methodsConfiguration[$method];
        $steps = $config[ConfigurableProducerInterface::CONF_STEPS];

        $context = $this->getConfHelper()->createContext($options, $exchange->getIn(), $exchange);

        $this->configurableStepsProvider->executeSteps($steps, $options, $context);

        $empty = [];  //We would like to pass as a referance, so this must be a variable
        $this->configurableStepsProvider->executeStep(CsvConfigurableStepsProvider::STEP_CLEAN_FILE_HANDLES, $empty, $options, $context);
    }
}