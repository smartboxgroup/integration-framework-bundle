<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Evaluator;

use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class CustomExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            $this->createHasHeyFunction(),
            $this->createIsRecoverableFunction(),
        ];
    }

    /**
     * @return ExpressionFunction
     */
    protected function createHasHeyFunction()
    {
        return new ExpressionFunction(
            'hasKey',
            function ($key, $array) {
                return sprintf('(array_key_exists(%s,%s))', $key, $array);
            },
            function ($arguments, $key, $array) {
                if (!is_array($array)) {
                    throw new \RuntimeException('Second argument passed to "hasKey" should be an array.');
                }

                return array_key_exists($key, $array);
            }
        );
    }

    /**
     * @return ExpressionFunction
     */
    protected function createIsRecoverableFunction()
    {
        return new ExpressionFunction(
            'isRecoverable',
            function ($object) {
                return sprintf('(%s instanceof \Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface)', $object);
            },
            function ($arguments, $object) {
                if (!is_object($object) || !($object instanceof \Exception)) {
                    throw new \RuntimeException('First argument should be an exception');
                }

                return $object instanceof RecoverableExceptionInterface;
            }
        );
    }
}
