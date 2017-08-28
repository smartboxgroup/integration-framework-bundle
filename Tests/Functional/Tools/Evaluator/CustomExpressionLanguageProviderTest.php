<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Tools\Evaluator;

use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\CustomExpressionLanguageProvider;

/**
 * Class ExpressionEvaluatorTest.
 *
 * @coversDefaultClass \Smartbox\Integration\FrameworkBundle\Tools\Evaluator\CustomExpressionLanguage
 */
class CustomExpressionLanguageProviderTest extends TestCase
{
    public function testremoveNewLines()
    {
        $input = "Apartment 999, the beginning of the world\nSecond line of the address\n";
        $expectedOutput = "Apartment 999, the beginning of the world Second line of the address";

        $customExpressionLanguageProvider = new CustomExpressionLanguageProvider();

        //$this->assertEquals($expectedOutput, $customExpressionLanguageProvider->removeNewLines($input));

    }
}
