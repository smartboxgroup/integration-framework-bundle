<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\NoSQLConfigurableProtocol;
use Smartbox\Integration\FrameworkBundle\Configurability\IsConfigurableService;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ConfigurableProducerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\Service;

class DBConfigurableProducer extends Service implements ConfigurableProducerInterface
{
    use IsConfigurableService;

    /** @var ConfigurableStepsProviderInterface */
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
        $method = $options[NoSQLConfigurableProtocol::OPTION_METHOD];
        $config = $this->methodsConfiguration[$method];
        $steps = $config[ConfigurableProducerInterface::CONF_STEPS];

        $context = $this->getConfHelper()->createContext($options, $exchange->getIn(), $exchange);

        $this->configurableStepsProvider->executeSteps($steps, $options, $context);

        $this->getCofHelper()->runValidations(@$config[ConfigurableProducerInterface::CONF_VALIDATIONS], $context);

        if (
            $options[Protocol::OPTION_EXCHANGE_PATTERN] == Protocol::EXCHANGE_PATTERN_IN_OUT &&
            array_key_exists(ConfigurableProducerInterface::CONF_RESPONSE, $config)
        ) {
            $result = $this->getConfHelper()->resolve($config[ConfigurableProducerInterface::CONF_RESPONSE], $context);

            if (is_array($result)) {
                $result = new SerializableArray($result);
            }

            $exchange->getIn()->setBody($result);
        }
    }
}
