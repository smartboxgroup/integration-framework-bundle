<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\EventListener;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Smartbox\Integration\FrameworkBundle\EventListener\EventsLoggerListener;
use Smartbox\Integration\FrameworkBundle\Events\Event;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Events\FakeErrorEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EventsLoggerListenerTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\EventListener
 */
class EventsLoggerListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventsLoggerListener */
    private $listener;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var string */
    private $logLevel = LogLevel::DEBUG;

    public function setUp()
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        /** @var RequestStack $requestStack */
        $requestStack = $this->getMockBuilder(RequestStack::class)->getMock();
        $this->listener = new EventsLoggerListener($this->logger, $requestStack, $this->logLevel);
    }

    public function testItShouldLogEventWhenItOccurs()
    {
        /** @var Event|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockForAbstractClass(Event::class);

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with($this->logLevel, $this->isType('string'), ['event_details' => $event])
        ;

        $this->listener->onEvent($event);
    }
}
