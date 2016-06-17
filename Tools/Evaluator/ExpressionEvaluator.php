<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Evaluator;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesMapper;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class ExpressionEvaluator
 */
class ExpressionEvaluator
{
    use UsesSerializer;
    use UsesMapper;

    /** @var ExpressionLanguage */
    protected $language;

    public function __construct()
    {
        $cache = new ApcuParserCache();
        $this->language = new ExpressionLanguage($cache);
        $this->registerLanguageFunctions($this->language);
    }

    /**
     * Method which registers additional functions to expression language
     * Registered functions:
     *  - array_key_exists
     *
     * @param ExpressionLanguage $language
     */
    protected function registerLanguageFunctions(ExpressionLanguage $language)
    {
        $language->register(
            'array_key_exists',
            function ($key, $array) {
                return sprintf('(array_key_exists($s,$s)', $key, $array);
            },
            function ($arguments, $key, array $array) {
                return array_key_exists($key, $array);
            }
        );
    }

    public function getExchangeExposedVars()
    {
        return array(
            'exchange',
            'msg',
            'headers',
            'body',
            'serializer',
            'mapper',
            'now',
        );
    }

    public function evaluateWithVars($expression, $vars)
    {
        $vars = array_merge($vars, [
            'serializer' => $this->getSerializer(),
            'mapper' => $this->getMapper(),
        ]);

        return $this->language->evaluate($expression, $vars);
    }

    public function evaluateWithExchange($expression, Exchange $exchange)
    {
        $body = $exchange->getIn()->getBody();

        return $this->language->evaluate($expression, array(
            'exchange' => $exchange,
            'msg' => $exchange->getIn(),
            'headers' => $exchange->getIn()->getHeaders(),
            'body' => $body,
            'serializer' => $this->getSerializer(),
            'mapper' => $this->getMapper(),
            'now' => new \DateTime(),
        ));
    }

    /**
     * @param $expression
     * @param array $names
     *
     * @return string
     */
    public function compile($expression, $names = array())
    {
        return $this->language->compile($expression, $names);
    }
}
