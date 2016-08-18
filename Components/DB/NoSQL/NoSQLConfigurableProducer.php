<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL;


use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ConfigurableProducerInterface;

class NoSQLConfigurableProducer extends NoSQLConfigurableService implements ConfigurableProducerInterface{

    /**
     * {@inheritdoc}
     */
    public function send(Exchange $exchange, EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        $method = $options[NoSQLConfigurableProtocol::OPTION_METHOD];
        $config = $this->methodsConfiguration[$method];
        $steps = $config[self::CONF_STEPS];

        $context = [
            'exchange' => $exchange,
            'msg' => $exchange->getIn(),
            'headers' => $exchange->getIn()->getHeaders(),
            'body' => $exchange->getIn()->getBody()
        ];

        $this->executeSteps($steps, $options, $context);

        if(@$config[self::CONF_RESULT]){
            $result = $this->getConfHelper()->resolve($config[self::CONF_RESULT],$context);
            $exchange->setOut($result);
        }
    }
}