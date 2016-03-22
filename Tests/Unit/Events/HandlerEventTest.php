<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Event;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Events\HandlerEvent;

/**
 * Class HandlerEventTest.
 */
class HandlerEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var HandlerEvent|\PHPUnit_Framework_MockObject_MockObject */
    private $event;

    public function setup()
    {
        $this->event = $this->getMockForAbstractClass(HandlerEvent::class);
    }

    public function testItShouldGetAndSetExchange()
    {
        $exchange = $this->getMock(Exchange::class);
        $this->event->setExchange($exchange);
        $this->assertSame($exchange, $this->event->getExchange());
    }
}
