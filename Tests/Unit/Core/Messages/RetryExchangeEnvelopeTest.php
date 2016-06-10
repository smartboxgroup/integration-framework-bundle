<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Messages;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\RetryExchangeEnvelope;

class RetryExchangeEnvelopeTest extends \PHPUnit_Framework_TestCase
{
    /** @var RetryExchangeEnvelope */
    private $retryExchangeEnvelope;

    public function setUp()
    {
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(Context::class);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));

        $exchange = new Exchange($message);

        $this->retryExchangeEnvelope = new RetryExchangeEnvelope($exchange);
    }

    public function tearDown()
    {
        $this->retryExchangeEnvelope = null;
    }

    public function testConstruct()
    {
        $context           = new Context;
        $message           = new Message(null, [], $context);
        $exchange          = new Exchange($message);
        $processingContext = new SerializableArray(['a' => 'b']);

        $retryExchangeEnvelope = new RetryExchangeEnvelope($exchange, $processingContext, 5);

        $this->assertSame($exchange, $retryExchangeEnvelope->getExchange());
        $this->assertSame($processingContext, $retryExchangeEnvelope->getProcessingContext());
        $this->assertSame(5, $retryExchangeEnvelope->getRetries());
    }

    public function testSetAndGetRetries()
    {
        $this->retryExchangeEnvelope->setRetries(3);

        $this->assertSame(3, $this->retryExchangeEnvelope->getRetries());
    }

    public function invalidRetryValuesProvider()
    {
        return [
            'Retry is not an integer'     => ['retries' => '-1'],
            'Retry is a negative integer' => ['retries' => -1],
        ];
    }

    /**
     * @dataProvider invalidRetryValuesProvider
     *
     * @param $retries
     */
    public function testSetRetriesWhenParamIsNotAnInteger($retries)
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->retryExchangeEnvelope->setRetries($retries);
    }
}