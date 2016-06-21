<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Evaluator;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class ExpressionLanguageFactory
 */
class ExpressionLanguageFactory
{
    public function createExpressionLanguage()
    {
        $language = new ExpressionLanguage(new ApcuParserCache());
        $language->registerProvider(new StringExpressionLanguageProvider());

        return $language;
    }
}
