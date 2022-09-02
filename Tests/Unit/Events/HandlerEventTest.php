<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Event;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Events\HandlerEvent;

/**
 * Class HandlerEventTest.
 */
class HandlerEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var HandlerEvent|\PHPUnit_Framework_MockObject_MockObject */
    private $event;

    public function setUp(): void
    {
        $this->event = $this->getMockForAbstractClass(HandlerEvent::class);
    }

    public function testItShouldGetAndSetExchange()
    {
        $exchange = $this->createMock(Exchange::class);
        $this->event->setExchange($exchange);
        $this->assertSame($exchange, $this->event->getExchange());
    }
}
