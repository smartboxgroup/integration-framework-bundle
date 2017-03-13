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
            $this->createGetFirstFunction(),
            $this->createIsRecoverableFunction(),
            $this->createContainsFunction(),
            $this->createUniqIdFunction(),
            $this->createSubStrFunction(),
            $this->createDefaultTo(),
        ];
    }

    /**
     * @return ExpressionFunction
     */
    protected function createContainsFunction()
    {
        return new ExpressionFunction(
            'contains',
            function ($string, $search) {
                return sprintf('( strpos(%s,%s) !== false )', $string, $search);
            },
            function ($arguments, $string, $search) {
                if (!is_string($string) || !is_string($search)) {
                    throw new \RuntimeException('Both arguments passed to "contains" should be strings.');
                }

                return strpos($string, $search) !== false;
            }
        );
    }

    /**
     * @return ExpressionFunction
     */
    protected function createDefaultTo()
    {
        return new ExpressionFunction(
            'defaultTo',
            function ($value, $defaultValue) {
                return sprintf('(%s !== null ? %s : %s)', $value, $value, $defaultValue);
            },
            function ($arguments, $value, $defaultValue) {
                return $value !== null ? $value : $defaultValue;
            }
        );
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
    protected function createGetFirstFunction()
    {
        return new ExpressionFunction(
            'getFirst',
            function ($array) {
                return sprintf('reset(%s)', $array);
            },
            function ($arguments, $array) {
                if (!is_array($array)) {
                    throw new \RuntimeException('First argument passed to "getFirst" should be an array.');
                }

                return reset($array);
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

    /**
     * @return ExpressionFunction
     */
    protected function createUniqIdFunction()
    {
        return new ExpressionFunction(
            'uniqid',
            function () {
                return '( uniqid() )';
            },
            function ($arguments) {
                return uniqid();
            }
        );
    }

    /**
     * @return ExpressionFunction
     */
    protected function createSubStrFunction()
    {
        return new ExpressionFunction(
            'substr',
            function ($string, $start, $length) {
                return sprintf('( is_string(%1$1) && substr(%1$s, %2$s, %3$s) : %1$s ) === false ? "" : substr(%1$s, %2$s, %3$s) : %1$s ) )', $string, $start, $length);
            },
            function ($arguments, $string, $start, $length) {

                if( !is_string($string)  ){
                    return "";
                }

                $sub_string =  substr($string, $start, $length);
                if( $sub_string === false){
                    return "";
                }

                return $sub_string;
            }
        );
    }
}
