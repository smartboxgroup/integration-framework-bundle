<?php
namespace Smartbox\Integration\FrameworkBundle\Configurability;

use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigurableServiceHelper
{
    use UsesEvaluator;
    use UsesSerializer;

    const KEY_STEPS = 'steps';
    const STEP_DEFINE = 'define';
    const OPTION_METHOD = 'method';
    const KEY_VARS = 'vars';
    const KEY_DESCRIPTION = 'description';
    const KEY_RESPONSE = 'response';

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
            $availableVars = array_merge($context, $context[self::KEY_VARS]);

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

    /**
     * @param $definitions
     * @param array $context
     * @throws InvalidConfigurationException
     */
    public function define($definitions, array &$context)
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
}