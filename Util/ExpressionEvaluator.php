<?php
namespace Smartbox\Integration\FrameworkBundle\Util;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionEvaluator
{
    /** @var ExpressionLanguage */
    protected $language;

    public function __construct()
    {
        $cache = new ApcParserCache();
        $this->language = new ExpressionLanguage($cache);
        // TODO: Register any providers here
    }

    // TODO: Define behaviour for this if
    public function evaluate($expression, $data = array())
    {
        // TODO: Add here to $data any info that we wish to have always available in the expressions
        return $this->language->evaluate($expression, $data);
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