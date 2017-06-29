<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Tools\Evaluator;

use Smartbox\Integration\FrameworkBundle\Tests\Functional\BaseTestCase;
use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\CustomExpressionLanguageProvider;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class CustomExpressionLanguageTest.
 * @group ExpressionLanguage
 * @coversDefaultClass \Smartbox\Integration\FrameworkBundle\Tools\Evaluator\CustomExpressionLanguageProvider
 */
class CustomExpressionLanguageTest extends BaseTestCase
{
    /** @var CustomExpressionLanguageProvider|ExpressionFunctionProviderInterface */
    protected $evaluator;

    public function setUp()
    {
        $this->bootKernel(['debug' => false]);
        $this->evaluator = static::$kernel->getContainer()->get('smartesb.util.evaluator');
    }

    public function tearDown()
    {
        $this->evaluator = null;
    }

    /**
     * @return array
     */
    public function dataProviderForExpressionsEvaluatedWithVars()
    {
        return [
            'Check isInt returns true when passed an integer' => [
                'expected' => true,
                'expression' => 'isInt(3)',
                'vars' => [],
            ],
            'Check isInt returns false when passed a string' => [
                'expected' => false,
                'expression' => 'isInt("string")',
                'vars' => [],
            ],
            'Check isInt returns false when passed an array' => [
                'expected' => false,
                'expression' => 'isInt([4,5])',
                'vars' => [],
            ],
            'Check isInt returns false when passed an null' => [
                'expected' => false,
                'expression' => 'isInt(null)',
                'vars' => [],
            ],
            'Check explode array' => [
                'expected' => ['this','is','a','test'],
                'expression' => 'explode(",","this,is,a,test")',
                'vars' => [],
            ],
            'Check explode array with single element' => [
                'expected' => ['this'],
                'expression' => 'explode(",","this")',
                'vars' => [],
            ],
            'Check explode array with single element and trailing delimiter' => [
                'expected' => ['this',''],
                'expression' => 'explode(",","this,")',
                'vars' => [],
            ],
            'Check explode when passed null return an array with 1 empty element' => [
                'expected' => [''],
                'expression' => 'explode(",",null)',
                'vars' => [],
            ],
            'Check implode returns a correctly formatted string' => [
                'expected' => "this_is_a_test",
                'expression' => 'implode("_",["this","is","a","test"])',
                'vars' => [],
            ],
            'Check implode returns a correctly formatted string when passed an array with only one element' => [
                'expected' => "this",
                'expression' => 'implode("_",["this"])',
                'vars' => [],
            ]
        ];
    }

    /**
     * @covers ::evaluateWithVars
     * @dataProvider dataProviderForExpressionsEvaluatedWithVars
     *
     * @param $expected
     * @param $expression
     * @param array $vars
     */
    public function testEvaluateWithVars($expected, $expression, array $vars)
    {
        $this->assertEquals($expected, $this->evaluator->evaluateWithVars($expression, $vars));
    }


}
