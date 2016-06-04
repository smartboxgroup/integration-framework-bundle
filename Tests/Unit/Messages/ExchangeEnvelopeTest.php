<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Messages;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Core\Messages\ExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

class ExchangeEnvelopeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExchangeEnvelope
     */
    private $exchangeEnvelope;

    public function setUp()
    {
        $context = $this->createMock(Context::class);

        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));

        $exchange = new Exchange($message);

        $this->exchangeEnvelope = new ExchangeEnvelope($exchange);
    }

    public function tearDown()
    {
        $this->exchangeEnvelope = null;
    }

    public function testGetExchange()
    {
        $this->assertInstanceOf(Exchange::class, $this->exchangeEnvelope->getExchange());
    }

    public function testSetAndGetBody()
    {
        $exchange = new Exchange;

        $this->exchangeEnvelope->setBody($exchange);

        $this->assertInstanceOf(Exchange::class, $this->exchangeEnvelope->getBody());
    }

    public function testSetBodyWhenBodyIsNotAnExchange()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->exchangeEnvelope->setBody();
    }
}