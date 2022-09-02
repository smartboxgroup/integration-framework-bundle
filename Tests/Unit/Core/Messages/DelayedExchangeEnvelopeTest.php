<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Messages;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\DelayedExchangeEnvelope;

class DelayedExchangeEnvelopeTest extends \PHPUnit\Framework\TestCase
{
    /** @var DelayedExchangeEnvelope */
    private $delayedExchangeEnvelope;

    protected function setUp(): void
    {
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(Context::class);

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        $exchange = new Exchange($message);

        $this->delayedExchangeEnvelope = new DelayedExchangeEnvelope($exchange, 0);
    }

    protected function tearDown(): void
    {
        $this->delayedExchangeEnvelope = null;
    }

    public function testConstruct()
    {
        $context = new Context();
        $message = new Message(null, [], $context);
        $exchange = new Exchange($message);
        $delayPeriod = 10;

        $delayedExchangeEnvelope = new DelayedExchangeEnvelope($exchange, $delayPeriod);

        $this->assertSame($exchange, $delayedExchangeEnvelope->getExchange());
        $this->assertSame($delayPeriod, $delayedExchangeEnvelope->getHeader(DelayedExchangeEnvelope::HEADER_DELAY_PERIOD));
    }
}
