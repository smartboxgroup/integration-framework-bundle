<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Messages;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\RetryExchangeEnvelope;

class RetryExchangeEnvelopeTest extends \PHPUnit_Framework_TestCase
{
    private $retryExchangeEnvelope;

    public function setUp()
    {
        $context = $this->createMock(Context::class);

        $message = $this->createMock(MessageInterface::class);
        $message
            ->method('getContext')
            ->will($this->returnValue($context));

        $exchange = new Exchange($message);

        $this->retryExchangeEnvelope = new RetryExchangeEnvelope($exchange);
    }

    public function tearDown()
    {
        $this->retryExchangeEnvelope = null;
    }

    public function testSetAndGetRetries()
    {
        $this->retryExchangeEnvelope->setRetries(3);

        $this->assertSame(3, $this->retryExchangeEnvelope->getRetries());
    }

    public function testSetRetriesWhenParamIsNotAnInteger()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->retryExchangeEnvelope->setRetries('-1');
    }
}