<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Handlers;

use Smartbox\Integration\FrameworkBundle\Events\Event;
use Smartbox\Integration\FrameworkBundle\Handlers\DeferredEventsHandler;
use Smartbox\Integration\FrameworkBundle\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Messages\EventMessage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DeferredEventsHandlerTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Functional\Handlers
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Handlers\DeferredEventsHandler
 */
class DeferredEventsHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DeferredEventsHandler */
    public $handler;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    public $eventDispatcherMock;

    public function setUp()
    {
        $this->eventDispatcherMock = $this->getMock(EventDispatcherInterface::class);
        $this->handler = new DeferredEventsHandler();
        $this->handler->setEventDispatcher($this->eventDispatcherMock);
        $this->handler->setFlowsVersion(0);
    }

    /**
     * @covers ::handle
     */
    public function testHandle()
    {
        /** @var Event|\PHPUnit_Framework_MockObject_MockObject $eventMock */
        $eventMock = $this->getMockForAbstractClass(Event::class,array('test'));
        $message = new EventMessage($eventMock, [EventMessage::HEADER_EVENT_NAME => 'test'], new Context([Context::VERSION => '0']));

        $this->eventDispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with('test.deferred', $message->getBody())
        ;

        $this->handler->handle($message);
    }
}