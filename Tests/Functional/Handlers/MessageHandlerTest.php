<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Handlers;

use Smartbox\Integration\FrameworkBundle\Connectors\QueueConnector;
use Smartbox\Integration\FrameworkBundle\Drivers\Queue\ArrayQueueDriver;
use Smartbox\Integration\FrameworkBundle\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Smartbox\Integration\FrameworkBundle\Events\Error\ProcessingErrorEvent;
use Smartbox\Integration\FrameworkBundle\Tests\EntityX;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Processors\Itinerary;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Processors\FakeProcessor;

/**
 * Class AsyncHandlerTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Functional\Handlers
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Handlers\AsyncHandler
 */
class MessageHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var MessageHandler */
    public $handler;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    public $eventDispatcherMock;

    public function setUp()
    {
        $this->eventDispatcherMock = $this->getMock(EventDispatcherInterface::class);
        $this->handler = new MessageHandler();
        $this->handler->setEventDispatcher($this->eventDispatcherMock);
    }

    public function dataProviderForNumberOfProcessors()
    {
        return [
            [1],
            [5],
            [9],
        ];
    }

    /**
     * @covers ::handle
     * @dataProvider dataProviderForNumberOfProcessors
     *
     * @param int $numberOfProcessors
     */
    public function testHandle($numberOfProcessors)
    {
        $message = new Message(new EntityX(2));
        $message->setContext(new Context());
        $from = 'xxx';
        $itinerary = new Itinerary();

        $itinerariesRouterMock = $this->getMockBuilder(InternalRouter::class)->disableOriginalConstructor()->getMock();
        $itinerariesRouterMock
            ->expects($this->once())
            ->method('match')
            ->with($from)
        ->willReturn(array(
            InternalRouter::KEY_ITINERARY => $itinerary
        ));

        $this->handler->setItinerariesRouter($itinerariesRouterMock);


        /** @var Exchange $exchangeProcessedManually */
        $exchangeProcessedManually =  new Exchange(unserialize(serialize($message)));
        $exchangeProcessedManually->setItinerary(new Itinerary());
        for ($i = 1; $i <= $numberOfProcessors ; $i++) {
            $processor = new FakeProcessor($i);
            $processor->setEventDispatcher($this->eventDispatcherMock);
            $processor->process($exchangeProcessedManually);

            $itinerary->addProcessor($processor);
        }

        $result = $this->handler->handle($message,$from);

        $this->assertEquals($exchangeProcessedManually->getResult(), $result);
    }

    /**
     * @covers ::handle
     */
    public function testHandleWithErrorLogging()
    {
        $message = new Message(new EntityX(3));
        $message->setContext(new Context());
        $itinerary = new Itinerary();
        $fromURI = 'xxx';
        $failedUri = 'failed';
        $failedQueue = 'failed_queue';

        // Itineraries router mock
        $itinerariesRouterMock = $this->getMockBuilder(InternalRouter::class)->disableOriginalConstructor()->getMock();
        $itinerariesRouterMock
            ->expects($this->once())
            ->method('match')
            ->with($fromURI)
            ->willReturn(array(
                InternalRouter::KEY_ITINERARY => $itinerary
            ));

        $failedConnector = new QueueConnector();
        $failedQueueDriver = new ArrayQueueDriver();

        // Connectors router mock
        $connectorsRouterMock = $this->getMockBuilder(InternalRouter::class)->disableOriginalConstructor()->getMock();
        $connectorsRouterMock
            ->expects($this->once())
            ->method('match')
            ->with($failedUri)
            ->willReturn(array(
                InternalRouter::KEY_URI => $failedUri,
                InternalRouter::KEY_CONNECTOR => $failedConnector,
                QueueConnector::OPTION_QUEUE_DRIVER => $failedQueueDriver,
                QueueConnector::OPTION_QUEUE_NAME => $failedQueue

            ));

        $this->handler->setItinerariesRouter($itinerariesRouterMock);
        $this->handler->setFailedURI($failedUri);
        $this->handler->setConnectorsRouter($connectorsRouterMock);

        // --------------------
        // processor 1: success
        // --------------------
        $processor1 = new FakeProcessor(1);
        $processor1->setEventDispatcher($this->eventDispatcherMock);
        $itinerary->addProcessor($processor1);

        // --------------------
        // processor 2: error
        // --------------------
        $exception = new \Exception('Error while processing message by processor 2');
        $processor2 = new FakeProcessor(2, $exception);
        $processor2->setEventDispatcher($this->eventDispatcherMock);
        $itinerary->addProcessor($processor2);

        $dispatchedErrors = [];
        $this->eventDispatcherMock
            ->expects($this->any())
            ->method('dispatch')
            ->will($this->returnCallback(
                function ($eventType, $eventObject) use (&$dispatchedErrors) {
                    if ($eventType === ProcessingErrorEvent::EVENT_NAME) {
                        $dispatchedErrors[] = $eventObject;
                    }

                    return $eventObject;
                }
            ));
        ;

        // --------------------
        // processor 3: success
        // --------------------
        $processor3 = new FakeProcessor(3);
        $processor3->setEventDispatcher($this->eventDispatcherMock);
        $itinerary->addProcessor($processor3);


        $result = $this->handler->handle($message,$fromURI);

        $this->assertNull($result);
        $this->assertCount(1,$dispatchedErrors);
        $this->assertCount(1,$failedQueueDriver->getArrayForQueue($failedQueue));
    }
}