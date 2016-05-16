<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Producers;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ConfigurableProducer.
 */
abstract class ConfigurableProducer extends Producer implements ConfigurableProducerInterface
{
    use UsesEvaluator;
    use UsesSerializer;

    const OPTION_METHOD = 'method';

    const KEY_VARS = 'vars';
    const KEY_PRODUCER = 'producer';
    const KEY_PRODUCER_SHORT = 'c';
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

    /** @var  array */
    protected $methodsConfiguration;

    /** @var array  */
    protected $configuredOptions = [];

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->configuredOptions = array_merge($this->configuredOptions, $options);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->configuredOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function setMethodsConfiguration(array $methodsConfiguration)
    {
        $this->methodsConfiguration = $methodsConfiguration;
    }

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
        $context = [
            'exchange' => $exchange,
            'msg' => $exchange->getIn(),
            'headers' => $exchange->getIn()->getHeaders(),
            'body' => $exchange->getIn()->getBody(),
            'serializer' => $this->getSerializer(),
            self::KEY_VARS => [],
            self::KEY_PRODUCER => $this,
            self::KEY_PRODUCER_SHORT => $this,
            self::KEY_RESPONSES => [],
        ];

        /*
         * PROCESSING
         */
        foreach ($methodConf[self::KEY_STEPS] as $step) {
            foreach ($step as $stepAction => $stepActionParams) {
                $this->executeStep($stepAction, $stepActionParams, $options, $context);
            }
        }

        /*
         * VALIDATION
         */
        if (array_key_exists(self::KEY_VALIDATIONS, $methodConf)) {
            foreach ($methodConf[self::KEY_VALIDATIONS] as $validationRule) {
                $rule = $validationRule[self::KEY_RULE];
                $message = $validationRule[self::KEY_MESSAGE];
                $recoverable = $validationRule[self::KEY_RECOVERABLE];

                $evaluation = $this->resolve($rule, $context);
                if ($evaluation !== true) {
                    if ($recoverable) {
                        throw new ProducerRecoverableException($message);
                    } else {
                        throw new ProducerUnrecoverableException($message);
                    }
                }
            }
        }

        /*
         * RESPONSE
         */
        if ($options[Protocol::OPTION_EXCHANGE_PATTERN] == Protocol::EXCHANGE_PATTERN_IN_OUT
            &&  array_key_exists(self::KEY_RESPONSE, $methodConf)) {
            $resultConfig = $methodConf[self::KEY_RESPONSE];
            $result = $this->resolve($resultConfig, $context);

            if (is_array($result)) {
                $result = new SerializableArray($result);
            }
            $exchange->getOut()->setBody($result);
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
        switch ($stepAction) {
            case self::STEP_DEFINE:
                $this->define($stepActionParams, $context);

                return true;
            default:
                return false;
        }
    }

    /**
     * @param mixed $obj
     * @param array $context
     *
     * @return array|string
     */
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
            $availableVars = array_merge($context, $context[self::KEY_VARS]);

            return $this->evaluateStringOrExpression($obj, $availableVars);
        } else {
            return $obj;
        }
    }

    /**
     * @param string $string
     * @param array  $availableVars
     *
     * @return string
     */
    protected function evaluateStringOrExpression($string, &$availableVars)
    {
        $regex = '/^eval: (?P<expr>.+)$/i';
        $success = preg_match($regex, $string, $matches);

        if (!$success) {
            return $string;
        }

        $expression = $matches['expr'];

        return $this->evaluator->evaluateWithVars($expression, $availableVars);
    }

    /**
     * @param array $definitions
     * @param array $context
     */
    protected function define($definitions, array &$context)
    {
        if (!is_array($definitions)) {
            throw new InvalidConfigurationException(
                "Step 'define' in ConfigurableProducer expected an array as configuration"
            );
        }

        if (!array_key_exists(self::KEY_VARS, $context)) {
            $context[self::KEY_VARS] = [];
        }

        foreach ($definitions as $key => $definition) {
            $context[self::KEY_VARS][$key] = $this->resolve($definition, $context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsDescriptions()
    {
        $methodDescriptions = [];
        foreach($this->methodsConfiguration as $method => $methodConfig){
            $methodDescriptions[$method] = $methodConfig['description'];
        }

        $options = [
            self::OPTION_METHOD => ["Method of the producer to be executed",$methodDescriptions]
        ];

        foreach ($this->configuredOptions as $option => $value) {
            $options[$option] = ['Custom option added in configurable producer',[]];
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptionsResolver(OptionsResolver $resolver)
    {
        $resolver->setRequired([self::OPTION_METHOD]);
        $resolver->setAllowedValues(self::OPTION_METHOD,array_keys($this->methodsConfiguration));

        foreach ($this->configuredOptions as $option => $value) {
            $resolver->setDefault($option, $value);
        }
    }
}
