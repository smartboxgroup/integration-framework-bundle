<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Evaluator;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class ExpressionLanguageFactory.
 */
class ExpressionLanguageFactory
{
    public static function createExpressionLanguage()
    {
        $language = new ExpressionLanguage(new ApcuParserCache());
        $language->registerProvider(new CustomExpressionLanguageProvider());

        return $language;
    }
}
