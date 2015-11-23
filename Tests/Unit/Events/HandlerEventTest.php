<?php

namespace Smartbox\Integration\ServiceBusBundle\Tests\Event;

use Smartbox\Integration\FrameworkBundle\Events\HandlerEvent;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;

/**
 * Class HandlerEventTest
 * @package Smartbox\Integration\ServiceBusBundle\Tests\Event
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
