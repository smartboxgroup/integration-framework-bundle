<?php

namespace Smartbox\Integration\FrameworkBundle\Connectors;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Exceptions\ConnectorRecoverableException;
use Smartbox\Integration\FrameworkBundle\Exceptions\ConnectorUnrecoverableException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\Traits\UsesSerializer;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ConfigurableConnector
 * @package Smartbox\Integration\FrameworkBundle\Connectors
 */
abstract class ConfigurableConnector extends Connector implements ConfigurableConnectorInterface
{
    use UsesEvaluator;
    use UsesSerializer;

    const OPTION_TIMEOUT = 'timeout';
    const OPTION_METHOD = 'method';

    const KEY_VARS = 'vars';
    const KEY_CONNECTOR = 'connector';
    const KEY_CONNECTOR_SHORT = 'c';
    const KEY_RESPONSES = 'responses';
    const KEY_RESPONSE = 'response';
    const KEY_VALIDATIONS = 'validations';
    const KEY_DESCRIPTION = 'description';
    const KEY_RULE = 'rule';
    const KEY_MESSAGE = 'message';
    const KEY_RECOVERABLE = 'recoverable';
    const KEY_STEPS = 'steps';
    const STEP_DEFINE = 'define';
    const STEP_REQUEST = 'request';

    public static $SUPPORTED_EXCHANGE_PATTERNS = [self::EXCHANGE_PATTERN_IN_ONLY, self::EXCHANGE_PATTERN_IN_OUT];

    protected $methodsConfiguration;

    protected $methodsConfig;

    /** @var  OptionsResolver */
    protected $optionsResolver;

    protected $localDefaultOptions = array(
        self::OPTION_RETRIES => 5,
        self::OPTION_TIMEOUT => 500,        // ms
    );

    public function __construct(){
        parent::__construct();
        $available = $this->getAvailableOptions();

        $this->optionsResolver = new OptionsResolver();
        $this->optionsResolver->setDefaults($this->getDefaultOptions());
        $this->optionsResolver->setDefined(array_keys($available));
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultOptions()
    {
        return array_merge(
            parent::getDefaultOptions(),
            $this->localDefaultOptions
        );
    }

    public function setDefaultOptions(array $options){
        $this->localDefaultOptions = array_merge($this->localDefaultOptions,$options);
        $this->optionsResolver->setDefaults($this->getDefaultOptions());
    }

    public function getAvailableOptions(){
        $methods = [];

        if(!empty($this->methodsConfiguration)){
            foreach($this->methodsConfiguration as $method => $conf){
                $methods[$method] = $conf['description'];
            }
        }

        return array_merge(parent::getAvailableOptions(),[
            self::OPTION_METHOD => ['Method to be executed in the connector',$methods],
            self::OPTION_TIMEOUT => ['Timeout in seconds',[]]
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public static function validateOptions(array $options, $checkComplete = false)
    {
        return parent::validateOptions($options,$checkComplete);
    }

    public function setMethodsConfiguration(array $methodsConfiguration)
    {
        $this->methodsConfiguration = $methodsConfiguration;
    }

    public function send(Exchange $exchange, array $options)
    {
        $method = $options[self::OPTION_METHOD];
        if (!array_key_exists($method, $this->methodsConfiguration)) {
            throw new \InvalidArgumentException("Method $method was not configured in this connector");
        }

        /**
         * CONTEXT PREPARATION
         */
        $methodConf = $this->methodsConfiguration[$method];
        $context = [
            'exchange' => $exchange,
            'msg' => $exchange->getIn(),
            'headers' => $exchange->getIn()->getHeaders(),
            'body' => $exchange->getIn()->getBody(),
            'serializer' => $this->getSerializer(),
            self::KEY_VARS => [],
            self::KEY_CONNECTOR => $this,
            self::KEY_CONNECTOR_SHORT => $this,
            self::KEY_RESPONSES => []
        ];

        /**
         * PROCESSING
         */
        foreach ($methodConf[self::KEY_STEPS] as $step) {
            foreach ($step as $stepAction => $stepActionParams) {
                $this->executeStep($stepAction, $stepActionParams, $options, $context);
            }
        }

        /**
         * VALIDATION
         */
        if(array_key_exists(self::KEY_VALIDATIONS,$methodConf)){
            foreach($methodConf[self::KEY_VALIDATIONS] as $validationRule){
                $rule = $validationRule[self::KEY_RULE];
                $message = $validationRule[self::KEY_MESSAGE];
                $recoverable = $validationRule[self::KEY_RECOVERABLE];

                $evaluation = $this->resolve($rule,$context);
                if($evaluation !== true){
                    if($recoverable){
                        throw new ConnectorRecoverableException($message);
                    }else{
                        throw new ConnectorUnrecoverableException($message);
                    }
                }
            }
        }

        /**
         * RESPONSE
         */
        if(     $options[self::OPTION_EXCHANGE_PATTERN] == Connector::EXCHANGE_PATTERN_IN_OUT
            &&  array_key_exists(self::KEY_RESPONSE,$methodConf)){
            $resultConfig = $methodConf[self::KEY_RESPONSE];
            $result = $this->resolve($resultConfig,$context);

            if(is_array($result)){
                $result = new SerializableArray($result);
            }
            $exchange->getOut()->setBody($result);
        }
    }

    public function executeStep($stepAction, $stepActionParams, $options, array &$context)
    {
        switch ($stepAction) {
            case self::STEP_DEFINE:
                $this->define($stepActionParams, $context);
                break;
            case self::STEP_REQUEST:
                $this->request($stepActionParams, $options, $context);
                break;
        }
    }

    protected function resolve($obj, array &$context)
    {
        if (empty($obj)) {
            return $obj;
        } elseif (is_array($obj)) {
            $res = [];
            foreach ($obj as $key => $value) {
                $res[$key] = $this->resolve($value, $context);
            }

            return $res;
        } elseif (is_string($obj)) {
            return $this->evaluator->evaluateWithVars($obj, array_merge($context, $context[self::KEY_VARS]));
        } else {
            return $obj;
        }
    }

    protected function replaceTemplateVars($string, $context){
        $string = preg_replace('/\s+/', '',$string);

        // Prepare replacements
        $replacements = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $replacements['{'.$key.'}'] = $value;
            }
        }

        if(array_key_exists(self::KEY_VARS,$context)){
            foreach ($context[self::KEY_VARS] as $key => $value) {
                if (is_scalar($value)) {
                    $replacements['{'.$key.'}'] = $value;
                }
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $string);
    }

    protected function define($definitions, array &$context)
    {
        if (!is_array($definitions)) {
            throw new InvalidConfigurationException(
                "Step 'define' in ConfigurableConnector expected an array as configuration"
            );
        }

        if(!array_key_exists(self::KEY_VARS,$context)){
            $context[self::KEY_VARS] = [];
        }

        foreach ($definitions as $key => $definition) {
            $context[self::KEY_VARS][$key] = $this->resolve($definition, $context);
        }
    }

    protected abstract function request(array $stepActionParams, array $connectorOptions, array &$context);
}
