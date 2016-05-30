<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Tools\Logs;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Events\Event;
use Smartbox\Integration\FrameworkBundle\Events\HandlerEvent;
use Smartbox\Integration\FrameworkBundle\Events\ProcessEvent;
use Smartbox\Integration\FrameworkBundle\Tools\Logs\EventsLoggerListener;
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
        $this->logger = $this->getMock(LoggerInterface::class);
        /** @var RequestStack $requestStack */
        $requestStack = $this->getMock(RequestStack::class);
        $this->listener = new EventsLoggerListener($this->logger, $requestStack, $this->logLevel);
    }

    public function testItShouldLogEventWhenItOccurs()
    {
        $event = $this->getMockForAbstractClass(Event::class);

        $expectedContext = [
            'event_name'    => null,
            'event_details' => '',
        ];

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($this->logLevel),
                $this->isType('string'),
                $this->equalTo($expectedContext)
            );

        $this->listener->onEvent($event);
    }

    public function testItShouldLogEventWhenHandlerEventOccurs()
    {
        $exchange = $this->getMock(Exchange::class);
        $exchange
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('exchange_id'));
        $exchange
            ->expects($this->exactly(2))
            ->method('getHeader')
            ->withConsecutive(
                $this->equalTo('from'),
                $this->equalTo('async')
            )
            ->willReturnOnConsecutiveCalls('test://endpoint', false);

        $event = $this->getMock(HandlerEvent::class);
        $event
            ->expects($this->exactly(2))
            ->method('getExchange')
            ->will($this->returnValue($exchange));
        $event
            ->expects($this->exactly(2))
            ->method('getEventName')
            ->will($this->returnValue(HandlerEvent::BEFORE_HANDLE_EVENT_NAME));

        $expectedContext = [
            'event_name'    => HandlerEvent::BEFORE_HANDLE_EVENT_NAME,
            'event_details' => null,
            'exchange'      => [
                'id'     => 'exchange_id',
                'uri'    => 'test://endpoint',
                'type'   => 'sync',
                'detail' => $exchange
            ]
        ];

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($this->logLevel),
                $this->isType('string'),
                $this->equalTo($expectedContext)
            );

        $this->listener->onEvent($event);
    }

    public function testItShouldLogEventWhenProcessEventOccurs()
    {
        $exchange = $this->getMock(Exchange::class);
        $exchange
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('exchange_id'));
        $exchange
            ->expects($this->exactly(2))
            ->method('getHeader')
            ->withConsecutive(
                $this->equalTo('from'),
                $this->equalTo('async')
            )
            ->willReturnOnConsecutiveCalls('test://endpoint', true);

        $processor = $this->getMock(Processor::class);
        $processor
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('processor_1'));
        $processor
            ->expects($this->once())
            ->method('getDescription')
            ->will($this->returnValue('Processor 1 description'));

        $processingContext = $this->getMock(SerializableArray::class);

        $event = $this->getMock(ProcessEvent::class);
        $event
            ->expects($this->once())
            ->method('getExchange')
            ->will($this->returnValue($exchange));
        $event
            ->expects($this->exactly(2))
            ->method('getEventName')
            ->will($this->returnValue(ProcessEvent::TYPE_BEFORE));
        $event
            ->expects($this->once())
            ->method('getProcessor')
            ->will($this->returnValue($processor));
        $event
            ->expects($this->once())
            ->method('getProcessingContext')
            ->will($this->returnValue($processingContext));


        $expectedContext = [
            'event_name'    => ProcessEvent::TYPE_BEFORE,
            'event_details' => null,
            'exchange'      => [
                'id'     => 'exchange_id',
                'uri'    => 'test://endpoint',
                'type'   => 'async',
            ],
            'processor'     => [
                'id'          => 'processor_1',
                'name'        => get_class($processor),
                'description' => 'Processor 1 description',
            ]
        ];

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($this->logLevel),
                $this->isType('string'),
                $this->equalTo($expectedContext)
            );

        $this->listener->onEvent($event);
    }
}
