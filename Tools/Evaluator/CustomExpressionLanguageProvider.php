<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Evaluator;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class CustomExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return array(
            $this->createHasHeyFunction(),
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
                return sprintf('(array_key_exists($s,$s)', $key, $array);
            },
            function ($arguments, $key, $array) {
                if (!is_array($array)) {
                    throw new \RuntimeException('Second argument passed to "hasKey" should be an array.');
                }
                return array_key_exists($key, $array);
            }
        );
    }
}