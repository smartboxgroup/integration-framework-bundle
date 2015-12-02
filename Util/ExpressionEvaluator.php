<?php
namespace Smartbox\Integration\FrameworkBundle\Util;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionEvaluator
{
    /** @var ExpressionLanguage */
    protected $language;

    public function __construct()
    {
        $cache = new ApcParserCache();
        $this->language = new ExpressionLanguage($cache);
        // Register any providers here
    }

    public function getExchangeExposedVars(){
        return array(
            'exchange',
            'msg',
            'headers',
            'body'
        );
    }

    public function evaluateWithVars($expression, $vars)
    {
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
        ));
    }

    /**
     * @param $expression
     * @param array $names
     * @return string
     */
    public function compile($expression, $names = array())
    {
        return $this->language->compile($expression, $names);
    }

}