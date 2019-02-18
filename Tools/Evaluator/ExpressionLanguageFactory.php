<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Evaluator;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class ExpressionLanguageFactory.
 */
class ExpressionLanguageFactory
{
    public static function createExpressionLanguage(CacheItemPoolInterface $cache = null)
    {
        $language = new ExpressionLanguage($cache ?? new ApcuParserCache());
        $language->registerProvider(new CustomExpressionLanguageProvider());

        return $language;
    }
}
