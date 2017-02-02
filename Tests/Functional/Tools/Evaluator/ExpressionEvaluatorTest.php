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
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator
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
                'vars' => []
            ],
            'Check substr returns a blank string' => [
                'expected' => '',
                'expression' => 'substr("abcdef", 10, 3)',
                'vars' => []
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
