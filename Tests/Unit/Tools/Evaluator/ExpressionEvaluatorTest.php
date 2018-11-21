<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Tools\Evaluator;

use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class ExpressionEvaluatorTest.
 *
 * @coversDefaultClass \Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator
 */
class ExpressionEvaluatorTest extends TestCase
{
    /**
     * Test that evaluteWithVars method adds a the failed expression to the thrown error message.
     *
     * @covers ::evaluateWithVars
     */
    public function testEvaluateCatchesAndRethrowsEvaluationErrors()
    {
        $language = $this->createMock(ExpressionLanguage::class);
        $language->method('evaluate')
            ->willThrowException(new \RuntimeException('Original Message'));

        $failingExpression = 'expression';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp("/.*'expression'\\. Original Message.*/");

        $evaluator = new ExpressionEvaluator($language);
        $evaluator->evaluateWithVars($failingExpression, []);
    }
}
