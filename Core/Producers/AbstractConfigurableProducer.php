<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Producers;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;
use Smartbox\Integration\FrameworkBundle\Configurability\IsConfigurableService;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEventDispatcher;

/**
 * Class AbstractConfigurableProducer.
 */
abstract class AbstractConfigurableProducer extends Producer implements ConfigurableProducerInterface
{
    use IsConfigurableService;

    const KEY_PRODUCER = 'producer';
    const KEY_PRODUCER_SHORT = 'c';
    const KEY_RESPONSES = 'responses';
    const KEY_DESCRIPTION = 'description';
    const KEY_RULE = 'rule';
    const KEY_MESSAGE = 'message';
    const KEY_RECOVERABLE = 'recoverable';
    const STEP_REQUEST = 'request';

    /**
     * {@inheritdoc}
     */
    public function send(Exchange $exchange, EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();

        $method = $options[ConfigurableWebserviceProtocol::OPTION_METHOD];

        if (!array_key_exists($method, $this->methodsConfiguration)) {
            throw new \InvalidArgumentException("Method $method was not configured in this producer");
        }

        /*
         * CONTEXT PREPARATION
         */
        $methodConf = $this->methodsConfiguration[$method];
        $context = $this->getConfHelper()->createContext($options, $exchange->getIn(), $exchange);

        $context[self::KEY_PRODUCER] = $this;
        $context[self::KEY_PRODUCER_SHORT] = $this;

        /*
         * PROCESSING
         */
        foreach ($methodConf[ConfigurableProducerInterface::CONF_STEPS] as $step) {
            foreach ($step as $stepAction => $stepActionParams) {
                $this->executeStep($stepAction, $stepActionParams, $options, $context);
            }
        }

        $this->getConfHelper()->runValidations($methodConf[ConfigurableProducerInterface::CONF_VALIDATIONS], $context);

        /*
         * RESPONSE
         */
        if ($options[Protocol::OPTION_EXCHANGE_PATTERN] == Protocol::EXCHANGE_PATTERN_IN_OUT
            && array_key_exists(ConfigurableProducerInterface::CONF_RESPONSE, $methodConf)) {
            $resultConfig = $methodConf[ConfigurableProducerInterface::CONF_RESPONSE];
            $result = $this->confHelper->resolve($resultConfig, $context);

            $body = isset($result['body']) ? $result['body'] : null;
            if (is_array($body)) {
                $body = new SerializableArray($body);
            }
            $exchange->getOut()->setBody($body);

            if (isset($result['headers']) && is_array($result['headers']) ) {
                $exchange->getOut()->setHeaders($result['headers']);
            }
        }
    }

    /**
     * Returns true if the step was executed, false if the step was not recognized.
     *
     * @param       $stepAction
     * @param       $stepActionParams
     * @param       $options
     * @param array $context
     *
     * @return bool
     */
    public function executeStep($stepAction, &$stepActionParams, &$options, array &$context)
    {
        return $this->getConfHelper()->executeStep($stepAction, $stepActionParams, $options, $context);
    }
}
