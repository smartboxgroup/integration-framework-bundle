<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Handlers;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\ArrayQueueDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueProducer;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueProtocol;
use Smartbox\Integration\FrameworkBundle\Configurability\DriverRegistry;
use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerException;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\ItineraryResolver;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageFactory;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageFactoryInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\Core\Serializers\QueueSerializerInterface;
use Smartbox\Integration\FrameworkBundle\Events\ProcessingErrorEvent;
use Smartbox\Integration\FrameworkBundle\Tests\EntityX;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Processors\FakeProcessor;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AsyncHandlerTest.
 *
 * @coversDefaultClass \Smartbox\Integration\FrameworkBundle\Core\Handlers\MessageHandler
 */
class MessageHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MessageHandler */
    public $handler;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    public $eventDispatcherMock;

    /** @var ContainerInterface */
    public $fakeContainer;

    /** @var MessageFactoryInterface */
    public $factory;

    protected function setUp(): void
    {
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $this->handler = new MessageHandler();
        $this->handler->setId('test_id');
        $this->handler->setEventDispatcher($this->eventDispatcherMock);
        $this->factory = new MessageFactory();
        $this->factory->setFlowsVersion(0);
        $this->handler->setMessageFactory($this->factory);
        $this->fakeContainer = new Container();
        $this->handler->setContainer($this->fakeContainer);
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
        $message = $this->factory->createMessage(new EntityX(2));
        $from = 'xxx';
        $itinerary = new Itinerary();

        $itineraryResolverMock = $this->getMockBuilder(ItineraryResolver::class)->disableOriginalConstructor()->getMock();
        $itineraryResolverMock->method('filterItineraryParamsToPropagate')->willReturn([]);
        $itineraryResolverMock
            ->expects($this->once())
            ->method('getItineraryParams')
            ->with($from, '0')
            ->willReturn([InternalRouter::KEY_ITINERARY => $itinerary]);

        $this->handler->setItineraryResolver($itineraryResolverMock);

        /** @var Exchange $exchangeProcessedManually */
        $exchangeProcessedManually = new Exchange(\unserialize(\serialize($message)));
        $exchangeProcessedManually->setItinerary(new Itinerary());
        for ($i = 1; $i <= $numberOfProcessors; ++$i) {
            $processor = new FakeProcessor($i);
            $processor->setId("processor_$i");
            $processor->setEventDispatcher($this->eventDispatcherMock);
            $processor->process($exchangeProcessedManually);

            $this->fakeContainer->set($processor->getId(), $processor);
            $itinerary->addProcessorId($processor->getId());
        }

        $arr = [];
        $endpoint = new Endpoint($from, $arr, new Protocol(), null, null, $this->handler);
        $result = $endpoint->handle($message);

        $this->assertEquals($exchangeProcessedManually->getResult(), $result);
    }

    /**
     * @covers ::handle
     * @dataProvider dataProviderForNumberOfProcessors
     */
    public function testHandleWithWrongVersionMustFail()
    {
        $this->expectException(HandlerException::class);

        $message = $this->factory->createMessage(new EntityX(2));
        $from = 'direct://test';
        $itinerary = new Itinerary();

        $itineraryResolverMock = $this->getMockBuilder(ItineraryResolver::class)->disableOriginalConstructor()->getMock();
        $itineraryResolverMock->method('filterItineraryParamsToPropagate')->willReturn([]);
        $itineraryResolverMock
            ->expects($this->once())
            ->method('getItineraryParams')
            ->with($from, '0')
            ->willReturn([InternalRouter::KEY_ITINERARY => $itinerary]);

        $this->handler->setItineraryResolver($itineraryResolverMock);

        $arr = [];
        $endpoint = new Endpoint($from, $arr, new Protocol());

        $this->handler->handle($message, $endpoint);
    }

    /**
     * @covers ::handle
     */
    public function testHandleWithErrorLogging()
    {
        $message = $this->factory->createMessage(new EntityX(3));
        $itinerary = new Itinerary();
        $fromURI = 'xxx';
        $failedUri = 'failed';
        $failedQueue = 'failed_queue';

        // Itineraries router mock
        $itineraryResolverMock = $this->getMockBuilder(ItineraryResolver::class)->disableOriginalConstructor()->getMock();
        $itineraryResolverMock->method('filterItineraryParamsToPropagate')->willReturn([]);
        $itineraryResolverMock
            ->expects($this->once())
            ->method('getItineraryParams')
            ->with($fromURI, '0')
            ->willReturn([InternalRouter::KEY_ITINERARY => $itinerary]);

        $failedQueueDriver = new ArrayQueueDriver();
        $failedQueueDriver->setMessageFactory($this->factory);

        $driverRegistryMock = $this->createMock(DriverRegistry::class);
        $driverRegistryMock
            ->expects($this->once())
            ->method('getDriver')
            ->willReturn($failedQueueDriver);

        $serializer = $this->createMock(QueueSerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('encode')
            ->willReturnCallback(
                function ($message) {
                    return [
                        'body' => serialize($message),
                        'headers' => $message->getHeaders(),
                    ];
                }
            );

        $failedProducer = new QueueProducer();
        $failedProducer->setMessageFactory($this->factory);
        $failedProducer->setDriverRegistry($driverRegistryMock);
        $failedProducer->setSerializer($serializer);

        $optionsResolver = new OptionsResolver();

        $queueProtocol = new QueueProtocol(true, 3600);
        $queueProtocol->configureOptionsResolver($optionsResolver);

        $failedEndpointOptions = $optionsResolver->resolve([
            QueueProtocol::OPTION_QUEUE_DRIVER => 'array_queue_driver',
            QueueProtocol::OPTION_QUEUE_NAME => $failedQueue,
            QueueProtocol::OPTION_PREFIX => '',
        ]);

        $failedUriEndpoint = new Endpoint($failedUri, $failedEndpointOptions, $queueProtocol, $failedProducer);

        $endpointFactoryMock = $this->createMock(EndpointFactory::class);
        $endpointFactoryMock
            ->expects($this->once())
            ->method('createEndpoint')
            ->with($failedUri)
            ->willReturn($failedUriEndpoint);

        $this->handler->setItineraryResolver($itineraryResolverMock);
        $this->handler->setEndpointFactory($endpointFactoryMock);
        $this->handler->setFailedURI($failedUri);

        // --------------------
        // processor 1: success
        // --------------------
        $processor1 = new FakeProcessor(1);
        $processor1->setEventDispatcher($this->eventDispatcherMock);
        $processor1->setId('proc_1');
        $this->fakeContainer->set($processor1->getId(), $processor1);
        $itinerary->addProcessorId($processor1->getId());

        // --------------------
        // processor 2: error
        // --------------------
        $exception = new \Exception('Error while processing message by processor 2');
        $processor2 = new FakeProcessor(2, $exception);
        $processor2->setEventDispatcher($this->eventDispatcherMock);
        $processor2->setId('proc_2');
        $this->fakeContainer->set($processor2->getId(), $processor2);
        $itinerary->addProcessorId($processor2->getId());

        $dispatchedErrors = [];
        $this->eventDispatcherMock
            ->expects($this->any())
            ->method('dispatch')
            ->will($this->returnCallback(
                function ($eventType, $eventObject) use (&$dispatchedErrors) {
                    if (ProcessingErrorEvent::EVENT_NAME === $eventType) {
                        $dispatchedErrors[] = $eventObject;
                    }

                    return $eventObject;
                }
            ));

        // --------------------
        // processor 3: success
        // --------------------
        $processor3 = new FakeProcessor(3);
        $processor3->setEventDispatcher($this->eventDispatcherMock);
        $processor3->setId('proc_3');
        $this->fakeContainer->set($processor3->getId(), $processor3);
        $itinerary->addProcessorId($processor3->getId());

        $arr = [];
        $endpoint = new Endpoint($fromURI, $arr, new Protocol());

        $result = $this->handler->handle($message, $endpoint);

        $this->assertNull($result);
        $this->assertCount(1, $dispatchedErrors);
        $this->assertCount(1, $failedQueueDriver->getArrayForQueue($failedQueue));
    }
}
