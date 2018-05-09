<?php

namespace Smartbox\Integration\FrameworkBundle\Configurability;

use Smartbox\Integration\FrameworkBundle\Core\Consumers\Exceptions\NoResultsException;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Exception\RecoverableExternalSystemException;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Exception\UnrecoverableExternalSystemException;
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
    const STEP_DEBUG = 'debug';

    const OPTION_METHOD = 'method';
    const KEY_DESCRIPTION = 'description';
    const KEY_RESPONSE = 'response';
    const CONF_DISPLAY_MESSAGE = 'display_message';

    /** @var OptionsResolver */
    protected $validateResolver;

    public function __construct()
    {
        $this->validateResolver = new OptionsResolver();
        $this->validateResolver->setRequired([self::CONF_RULE]);
        $this->validateResolver->setDefaults([
            self::CONF_NO_RESULTS => false,
            self::CONF_DISPLAY_MESSAGE => false,
            self::CONF_RECOVERABLE => false,
            self::CONF_MESSAGE => 'Validation not passed',
        ]);

        $this->validateResolver->setAllowedTypes(self::CONF_RECOVERABLE, 'boolean');
        $this->validateResolver->setAllowedTypes(self::CONF_NO_RESULTS, 'boolean');
        $this->validateResolver->setAllowedTypes(self::CONF_DISPLAY_MESSAGE, 'boolean');
        $this->validateResolver->setAllowedTypes(self::CONF_MESSAGE, 'string');
        $this->validateResolver->setAllowedTypes(self::CONF_RULE, 'string');
    }

    /**
     * @param array            $options
     * @param MessageInterface $message
     * @param Exchange         $exchange
     * @param \Exception       $exception
     *
     * @return array
     */
    public function createContext(array $options, MessageInterface $message = null, Exchange $exchange = null, \Exception $exception = null)
    {
        $context = [
            self::CONTEXT_OPTIONS => $options,
            self::CONTEXT_VARS => [],
            self::CONTEXT_RESULTS => [],
        ];

        if ($exchange) {
            $context[self::CONTEXT_EXCHANGE] = $exchange;
            if (!$message) {
                $message = $exchange->getIn();
            }
        }

        if ($message) {
            $context[self::CONTEXT_MSG] = $message;
            $context[self::CONTEXT_HEADERS] = $message->getHeaders();
            $context[self::CONTEXT_BODY] = $message->getBody();
        }

        if ($exception) {
            $context[self::CONTEXT_EXCEPTION] = $exception;
        }

        return $context;
    }

    public function resolveArray($input, &$context)
    {
        $output = [];
        foreach ($input as $key => $value) {
            $output[$key] = $this->resolve($value, $context);
        }

        return $output;
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

        $vars = $availableVars;
        if (array_key_exists(self::CONTEXT_VARS, $availableVars)) {
            $vars = array_merge($availableVars, $availableVars[self::CONTEXT_VARS]);
        }

        return $this->evaluator->evaluateWithVars($matches['expr'], $vars);
    }

    public function executeStep($stepAction, &$stepActionParams, &$options, array &$context)
    {
        switch ($stepAction) {
            case self::STEP_DEFINE:
                $this->define($stepActionParams, $context);

                return true;
                break;
            case self::STEP_VALIDATE:
                $this->validate($stepActionParams, $context);

                return true;
                break;
            case self::STEP_DEBUG:
                $this->debug($stepActionParams, $context);

                return true;
            default:
                return false;
                break;
        }
    }

    /**
     * @param $definitions
     * @param array $context
     *
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

    public function validate(array $stepConfig, array &$context)
    {
        $config = $this->validateResolver->resolve($stepConfig);

        $rule = $config[self::CONF_RULE];
        $message = $config[self::CONF_MESSAGE];
        $recoverable = $config[self::CONF_RECOVERABLE];
        $no_results = $config[self::CONF_NO_RESULTS];
        $display_message = $config[self::CONF_DISPLAY_MESSAGE];

        $evaluation = $this->resolve($rule, $context);
        if (true !== $evaluation) {
            $message = $this->resolve($message, $context);
            if ($no_results) {
                throw new NoResultsException($message);
            } elseif ($recoverable) {
                $exception = new RecoverableExternalSystemException($message);
                $exception->setExternalSystemName($context['producer']->getName());
                $exception->setShowExternalSystemErrorMessage($display_message);
                throw $exception;
            } else {
                $exception = new UnrecoverableExternalSystemException($message);
                $exception->setExternalSystemName($context['producer']->getName());
                $exception->setShowExternalSystemErrorMessage($display_message);
                throw $exception;
            }
        }
    }

    /**
     * Nasty hack to be able to easily display context variables in producers.
     *
     * @param array $actions
     * @param $context
     */
    protected function debug($actions, &$context)
    {
        if (!is_array($actions)) {
            return;
        }
        foreach ($actions as $action => $parameters) {
            if ('display' == $action) {
                foreach ($parameters as $var) {
                    echo "\n";
                    echo 'context'.$var.' => ';
                    $command = 'print_r($context'.$var.');';
                    eval($command);
                }
                flush();
                ob_flush();
            }

            if ('sleep' == $action) {
                echo 'Sleeping for '.$parameters.' seconds due to sleep step...'."\n";
                sleep($parameters);
            }

            if ('exit' == $action && false !== $parameters) {
                echo 'Exit due to debug step'."\n";
                exit;
            }
        }

        return;
    }

    public function runValidations(array $validations, array &$context)
    {
        if (!empty($validations)) {
            foreach ($validations as $validationRule) {
                $this->validate($validationRule, $context);
            }
        }
    }
}
