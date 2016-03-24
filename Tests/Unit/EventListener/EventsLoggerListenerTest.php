<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\EventListener;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Smartbox\Integration\FrameworkBundle\Events\Event;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EventsLoggerListenerTest.
 */
class EventsLoggerListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Smartbox\Integration\FrameworkBundle\Tools\Logs\EventsLoggerListener */
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
        $this->listener = new \Smartbox\Integration\FrameworkBundle\Tools\Logs\EventsLoggerListener($this->logger, $requestStack, $this->logLevel);
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
