<?php
namespace Smartbox\Integration\FrameworkBundle\Configurability;

use Smartbox\Integration\FrameworkBundle\Core\Consumers\Exceptions\NoResultsException;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ConfigurableProducerInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\ExchangeAwareInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurableServiceHelper
{
    use UsesEvaluator;
    use UsesSerializer;

    const CONTEXT_EXCHANGE = 'exchange';
    const CONTEXT_MSG = 'msg';
    const CONTEXT_BODY = 'body';
    const CONTEXT_EXCEPTION = 'exception';
    const CONTEXT_OPTIONS = 'options';
    const CONTEXT_VARS = 'vars';
    const CONTEXT_RESULTS = 'results';

    const CONTEXT_HEADERS = 'headers';
    const CONF_RULE = 'rule';
    const CONF_MESSAGE = 'message';
    const CONF_RECOVERABLE = 'recoverable';

    const CONF_NO_RESULTS = 'noResults';
    const STEP_DEFINE = 'define';

    const STEP_VALIDATE = 'validate';
    const OPTION_METHOD = 'method';
    const KEY_DESCRIPTION = 'description';
    const KEY_RESPONSE = 'response';

    /** @var  OptionsResolver */
    protected $validateResolver;


    public function __construct(){
        $this->validateResolver = new OptionsResolver();
        $this->validateResolver->setRequired([self::CONF_RULE]);
        $this->validateResolver->setDefaults([
            self::CONF_NO_RESULTS => false,
            self::CONF_RECOVERABLE => false,
            self::CONF_MESSAGE => "Validation not passed",
        ]);

        $this->validateResolver->setAllowedTypes(self::CONF_RECOVERABLE,'boolean');
        $this->validateResolver->setAllowedTypes(self::CONF_NO_RESULTS,'boolean');
        $this->validateResolver->setAllowedTypes(self::CONF_MESSAGE,'string');
        $this->validateResolver->setAllowedTypes(self::CONF_RULE,'string');
    }


    /**
     * @param array $options
     * @param MessageInterface $message
     * @param Exchange $exchange
     * @param \Exception $exception
     * @return array
     */
    public function createContext(array $options, MessageInterface $message = null, Exchange $exchange = null, \Exception $exception = null){
        $context = [
            self::CONTEXT_OPTIONS => $options,
            self::CONTEXT_VARS => [],
            self::CONTEXT_RESULTS => [],
        ];

        if($exchange){
            $context[self::CONTEXT_EXCHANGE] = $exchange;
            if(!$message){
                $message = $exchange->getIn();
            }
        }

        if($message){
            $context[self::CONTEXT_MSG] = $message;
            $context[self::CONTEXT_HEADERS] = $message->getHeaders();
            $context[self::CONTEXT_BODY] = $message->getBody();
        }

        if($exception){
            $context[self::CONTEXT_EXCEPTION] = $exception;
        }

        return $context;
    }

    /**
     * @param mixed $obj
     * @param array $context
     *
     * @return array|string
     */
    public function resolve($obj, array &$context)
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
            $availableVars = array_merge($context, $context[self::CONTEXT_VARS]);

            return $this->evaluateStringOrExpression($obj, $availableVars);
        }

        return $obj;
    }

    /**
     * @param string $string
     * @param array  $availableVars
     *
     * @return string
     */
    public function evaluateStringOrExpression($string, array &$availableVars)
    {
        $regex = '/^eval: (?P<expr>.+)$/i';
        $success = preg_match($regex, $string, $matches);

        if (!$success) {
            return $string;
        }

        $expression = $matches['expr'];

        return $this->evaluator->evaluateWithVars($expression, $availableVars);
    }

    public function executeStep($stepAction, &$stepActionParams, &$options, array &$context){
        switch($stepAction) {
            case self::STEP_DEFINE:
                $this->define($stepActionParams, $context);

                return true;
                break;

            case self::STEP_VALIDATE:
                $this->validate($stepActionParams, $context);

                return true;
                break;
            default:
                return false;
                break;
        }
    }


    /**
     * @param $definitions
     * @param array $context
     * @throws InvalidConfigurationException
     */
    public function define(array $definitions, array &$context)
    {
        if (!is_array($definitions)) {
            throw new InvalidConfigurationException(
                "Step 'define' in AbstractConfigurableProducer expected an array as configuration"
            );
        }

        if (!array_key_exists(self::CONTEXT_VARS, $context)) {
            $context[self::CONTEXT_VARS] = [];
        }

        foreach ($definitions as $key => $definition) {
            $context[self::CONTEXT_VARS][$key] = $this->resolve($definition, $context);
        }
    }

    public function validate(array $stepConfig, array &$context){
        $config = $this->validateResolver->resolve($stepConfig);

        $rule = $config[self::CONF_RULE];
        $message = $config[self::CONF_MESSAGE];
        $recoverable = $config[self::CONF_RECOVERABLE];
        $no_results = $config[self::CONF_NO_RESULTS];

        $evaluation = $this->resolve($rule, $context);
        if ($evaluation !== true) {
            $message = $this->resolve($message,$context);
            if($no_results){
                throw new NoResultsException($message);
            }
            elseif ($recoverable) {
                throw new RecoverableException($message);
            } else {
                throw new \RuntimeException($message);
            }
        }
    }

    public function runValidations(array $validations, array &$context){
        if (!empty($validations)) {
            foreach ($validations as $validationRule) {
                $this->validate($validationRule,$context);
            }
        }
    }
}