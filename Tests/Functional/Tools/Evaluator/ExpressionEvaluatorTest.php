<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Tools\Evaluator;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class ExpressionEvaluatorTest.
 *
 * @coversDefaultClass \Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator
 */
class ExpressionEvaluatorTest extends KernelTestCase
{
    /**
     * @var ExpressionEvaluator
     */
    protected $evaluator;

    protected function setUp()
    {
        $this->bootKernel();
        $this->evaluator = static::$kernel->getContainer()->get('smartesb.util.evaluator');
    }

    protected function tearDown()
    {
        $this->evaluator = null;
    }

    /**
     * @return array
     */
    public function dataProviderForExpressionsEvaluatedWithVars()
    {
        return [
            'Simple expression' => [
                'expected' => 2,
                'expression' => '1 + 1',
                'vars' => [],
            ],
            'Checking hasKey expression for existing key' => [
                'expected' => true,
                'expression' => 'hasKey("existing_key", value)',
                'vars' => [
                    'value' => ['existing_key' => 'This key exists'],
                ],
            ],
            'Checking hasKey expression for not existing key' => [
                'expected' => false,
                'expression' => 'hasKey("not_existing_key", value)',
                'vars' => [
                    'value' => ['existing_key' => 'This key exists'],
                ],
            ],
            'Check substr returns a substring' => [
                'expected' => 'abc',
                'expression' => 'substr("abcdef", 0, 3)',
                'vars' => [],
            ],
            'Check substr returns a blank string' => [
                'expected' => '',
                'expression' => 'substr("abcdef", 10, 3)',
                'vars' => [],
            ],
            'Check md5 returns a hash' => [
                'expected' => '187ef4436122d1cc2f40dc2b92f0eba0',
                'expression' => 'md5("ab")',
                'var' => [],
            ],
            'Check numberFormat returns null if null is passed' => [
                'expected' => null,
                'expression' => 'numberFormat(null)',
                'vars' => [],
            ],
            'Check numberFormat returns a main land number format to 2 decimals and no thousands separator' => [
                'expected' => '123456,00',
                'expression' => 'numberFormat( 123456.001, 2, ",", "" )',
                'vars' => [],
            ],
            'Check count returns the number of elements in an array' => [
                'expected' => 2,
                'expression' => 'count(array)',
                'vars' => [
                    'array' => [
                        1,
                        2,
                    ],
                ],
            ],
            'Check slice getting elements from the array starting from position 2 until the end' => [
                'expected' => ['c', 'd', 'e'],
                'expression' => 'slice(["a", "b", "c", "d", "e"], 2)',
                'vars' => [],
            ],
            'Check slice getting 1 element from the array stating 2 positions from the end' => [
                'expected' => ['d'],
                'expression' => 'slice(["a", "b", "c", "d", "e"], -2, 1)',
                'vars' => [],
            ],
            'Check slice getting 3 elements from the array starting from the beginning' => [
                'expected' => ['a', 'b', 'c'],
                'expression' => 'slice(["a", "b", "c", "d", "e"], 0, 3)',
                'vars' => [],
            ],
            'Check explode array' => [
                'expected' => ['this', 'is', 'a', 'test'],
                'expression' => 'explode(",","this,is,a,test")',
                'vars' => [],
            ],
            'Check explode array with single element' => [
                'expected' => ['this'],
                'expression' => 'explode(",","this")',
                'vars' => [],
            ],
            'Check explode array with single element and trailing delimiter' => [
                'expected' => ['this', ''],
                'expression' => 'explode(",","this,")',
                'vars' => [],
            ],
            'Check explode when passed null return an array with 1 empty element' => [
                'expected' => [''],
                'expression' => 'explode(",",null)',
                'vars' => [],
            ],
            'Check implode returns a correctly formatted string' => [
                'expected' => 'this_is_a_test',
                'expression' => 'implode("_",["this","is","a","test"])',
                'vars' => [],
            ],
            'Check implode returns a correctly formatted string when passed an array with only one element' => [
                'expected' => 'this',
                'expression' => 'implode("_",["this"])',
                'vars' => [],
            ],
            'Check removeNewLines removes any new lines in a string' => [
                'expected' => 'Line 1 Line 2',
                'expression' => 'removeNewLines("\nLine 1\nLine 2\n")',
                'vars' => [],
            ],
            'Check strtoupper replaces lower case with upper case' => [
                'expected' => 'SUPER AWESOME',
                'expression' => 'strtoupper("Super awesome")',
                'vars' => [],
            ],
            'Check strtoupper does nothing on upper case text' => [
                'expected' => 'SUPER AWESOME',
                'expression' => 'strtoupper("SUPER AWESOME")',
                'vars' => [],
            ],
            'Checking getKey expression for not existing KEY index' => [
                'expected' => false,
                'expression' => 'getKey(array)',
                'vars' => [
                    'array' => ['VALUE_WITH_NO_KEY'],
                ],
            ],
            'Checking getKey expression for existing KEY index' => [
                'expected' => 'VALUE_WITH_KEY',
                'expression' => 'getKey(array)',
                'vars' => [
                    'array' => ['key' => 'VALUE_WITH_KEY'],
                ],
            ],
            'Checking getValue expression for existing VALUE index' => [
                'expected' => 'VALUE_WITH_VALUE_KEY',
                'expression' => 'getValue(array)',
                'vars' => [
                    'array' => ['value' => 'VALUE_WITH_VALUE_KEY'],
                ],
            ],
            'Checking getValue expression for existing VALUE index' => [
                'expected' => false,
                'expression' => 'getValue(array)',
                'vars' => [
                    'array' => ['VALUE_WITH_NO_VALUE_KEY'],
                ],
            ],
        ];
    }

    /**
     * @covers ::evaluateWithVars
     * @dataProvider dataProviderForExpressionsEvaluatedWithVars
     *
     * @param $expected
     * @param $expression
     * @param array $vars
     *
     * @throws \Exception
     */
    public function testEvaluateWithVars($expected, $expression, array $vars)
    {
        $this->assertEquals($expected, $this->evaluator->evaluateWithVars($expression, $vars));
    }

    /**
     * @return array
     */
    public function dataProviderForExpressionsEvaluatedWithExchange()
    {
        $body = new SerializableArray(
            [
                'a' => 1,
                'b' => 2,
                'c' => 3,
            ]
        );

        $messageHeaders = [
            Message::HEADER_EXPIRES => 3,
            Message::HEADER_QUEUE => 'queue_uri',
            Message::HEADER_FROM => 'from_uri',
        ];

        $message = new Message(
            $body,
            $messageHeaders
        );

        $exchange = new Exchange($message);
        $exchange->setHeader(Exchange::HEADER_HANDLER, 'handler');
        $exchange->setHeader(Exchange::HEADER_PARENT_EXCHANGE, 'parent_exchange');
        $exchange->setHeader(Exchange::HEADER_FROM, 'from_uri');

        return [
            'Simple expression to extract exchange' => [
                'expected' => $exchange,
                'expression' => 'exchange',
                'exchange' => $exchange,
            ],
            'Simple expression to extract message' => [
                'expected' => $message,
                'expression' => 'msg',
                'exchange' => $exchange,
            ],
            'Simple expression to extract headers' => [
                'expected' => $messageHeaders,
                'expression' => 'headers',
                'exchange' => $exchange,
            ],
            'Simple expression to extract body' => [
                'expected' => $message->getBody(),
                'expression' => 'body',
                'exchange' => $exchange,
            ],
        ];
    }

    /**
     * @covers ::evaluateWithExchange
     * @dataProvider dataProviderForExpressionsEvaluatedWithExchange
     *
     * @param $expected
     * @param $expression
     * @param Exchange $exchange
     */
    public function testEvaluateWithExchange($expected, $expression, Exchange $exchange)
    {
        $this->assertEquals($expected, $this->evaluator->evaluateWithExchange($expression, $exchange));
    }
}
