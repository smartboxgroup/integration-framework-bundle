<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Messages;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Core\Messages\ExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

class ExchangeEnvelopeTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExchangeEnvelope */
    private $exchangeEnvelope;

    protected function setUp()
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

        $this->exchangeEnvelope = new ExchangeEnvelope($exchange);
    }

    protected function tearDown()
    {
        $this->exchangeEnvelope = null;
    }

    public function testConstruct()
    {
        $context = new Context(['a' => 'b']);
        $message = new Message(null, [], $context);
        $exchange = new Exchange($message);

        $exchangeEnvelope = new ExchangeEnvelope($exchange);

        $this->assertSame($exchange, $exchangeEnvelope->getBody());
        $this->assertEmpty($exchangeEnvelope->getHeaders());
        $this->assertSame($context, $exchangeEnvelope->getContext());
    }

    public function testGetExchange()
    {
        $context = new Context(['a' => 'b']);
        $message = new Message(null, [], $context);
        $exchange = new Exchange($message);

        $exchangeEnvelope = new ExchangeEnvelope($exchange);

        $this->assertSame($exchange, $exchangeEnvelope->getExchange());
    }

    public function testSetAndGetBody()
    {
        $message = new Message(null, ['header' => 'ok']);
        $exchange = new Exchange($message);

        $this->exchangeEnvelope->setBody($exchange);

        $this->assertSame($exchange, $this->exchangeEnvelope->getBody());
    }

    public function testSetBodyWhenBodyIsNotAnExchange()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->exchangeEnvelope->setBody();
    }
}
