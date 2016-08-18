<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Evaluator;

use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;

/**
 * Class ApcuParserCache.
 */
class ApcuParserCache implements ParserCacheInterface
{
    /**
     * Saves an expression in the cache.
     *
     * @param string           $key        The cache key
     * @param ParsedExpression $expression A ParsedExpression instance to store in the cache
     */
    public function save($key, ParsedExpression $expression)
    {
        apcu_add($key, $expression);
    }

    /**
     * Fetches an expression from the cache.
     *
     * @param string $key The cache key
     *
     * @return ParsedExpression|null
     */
    public function fetch($key)
    {
        $cached = apcu_fetch($key);
        if (!$cached) {
            return;
        }

        return $cached;
    }
}
