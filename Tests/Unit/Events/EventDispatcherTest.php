<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Events;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\ArrayQueueDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueProducer;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueProtocol;
use Smartbox\Integration\FrameworkBundle\Configurability\DriverRegistry;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Serializers\QueueSerializerInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\SmartboxIntegrationFrameworkExtension;
use Smartbox\Integration\FrameworkBundle\Events\HandlerEvent;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageFactory;
use Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventDispatcher;
use Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventFilterInterface;
use Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventFiltersRegistry;
use Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventMessage;
use Smartbox\Integration\FrameworkBundle\Tools\Helper\SmartesbHelper;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldDeferEvent()
    {
        $filterPass = $this->createMock(EventFilterInterface::class);
        $filterPass->method('filter')->willReturn(true);

        $filtersRegistry = new EventFiltersRegistry();
        $filtersRegistry->addDeferringFilter($filterPass);

        $messageFactory = new MessageFactory();
        $messageFactory->setFlowsVersion(0);

        $queueDriver = new ArrayQueueDriver();
        $queueDriver->setMessageFactory($messageFactory);

        $event = new HandlerEvent();
        $event->setTimestampToCurrent();
        $helper = new SmartesbHelper();

        $deferredURI = 'queue://events';

        $container = new Container();
        $container->setParameter(SmartboxIntegrationFrameworkExtension::PARAM_DEFERRED_EVENTS_URI, $deferredURI);
        $container->set('smartesb.registry.event_filters', $filtersRegistry);
        $container->set('smartesb.helper', $helper);
        $container->setParameter('smartesb.flows_version', '0');

        $driverRegistry = new DriverRegistry();
        $driverRegistry->setDriver('array', $queueDriver);

        $serializer = $this->createMock(QueueSerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('encode')
            ->willReturnCallback(
                function ($message)  {
                    return [
                        'body' => serialize($message),
                        'headers' => $message->getHeaders(),
                    ];
                }
            );

        $protocol = new QueueProtocol(true, 3600);
        $producer = new QueueProducer();
        $producer->setDriverRegistry($driverRegistry);
        $producer->setSerializer($serializer);

        $resolver = new OptionsResolver();

        $protocol->configureOptionsResolver($resolver);
        $opts = $resolver->resolve([
            'queue' => 'test_queue',
            'queue_driver' => 'array',
        ]);

        $endpoint = new Endpoint($deferredURI, $opts, $protocol, $producer);

        $endpointFactoryMock = $this->createMock(EndpointFactory::class);
        $endpointFactoryMock
            ->expects($this->once())
            ->method('createEndpoint')
            ->with($deferredURI)
            ->willReturn($endpoint);

        $container->set('smartesb.endpoint_factory', $endpointFactoryMock);
        $container->setParameter('smartesb.enable_events_deferring', true);

        $helper->setContainer($container);

        $dispatcher = new EventDispatcher($container);
        $dispatcher->dispatch('test_event', $event);

        $messages = $queueDriver->getArrayForQueue('test_queue');

        $this->assertCount(1, $messages);
        /** @var QueueMessage $message */
        $message = unserialize($messages[0]);

        $this->assertInstanceOf(QueueMessage::class, $message);

        $this->assertInstanceOf(EventMessage::class, $message->getBody());

        $this->assertEquals($message->getBody()->getBody(), $event);
    }

    public function testShouldNotDeferEventIfDeferred()
    {
        $filterPass = $this->createMock(EventFilterInterface::class);
        $filterPass->method('filter')->willReturn(true);

        $filtersRegistry = new EventFiltersRegistry();
        $filtersRegistry->addDeferringFilter($filterPass);

        $queueDriver = new ArrayQueueDriver();

        $event = new HandlerEvent();
        $event->setTimestampToCurrent();

        $container = new Container();
        $container->set('smartesb.registry.event_filters', $filtersRegistry);
        $container->set(SmartboxIntegrationFrameworkExtension::QUEUE_DRIVER_PREFIX.'events', $queueDriver);
        $container->setParameter('smartesb.enable_events_deferring', true);

        $dispatcher = new EventDispatcher($container);
        $dispatcher->dispatch('test_event.deferred', $event);

        $messages = $queueDriver->getArrayForQueue('test_queue');

        $this->assertCount(0, $messages);
    }

    public function testShouldNotDeferEventIfDoesNotPassFilter()
    {
        $filterDoesNotPass = $this->createMock(EventFilterInterface::class);
        $filterDoesNotPass->method('filter')->willReturn(false);

        $filtersRegistry = new EventFiltersRegistry();
        $filtersRegistry->addDeferringFilter($filterDoesNotPass);

        $queueDriver = new ArrayQueueDriver();

        $event = new HandlerEvent();
        $event->setTimestampToCurrent();

        $container = new Container();
        $container->set('smartesb.registry.event_filters', $filtersRegistry);
        $container->set(SmartboxIntegrationFrameworkExtension::QUEUE_DRIVER_PREFIX.'events', $queueDriver);
        $container->setParameter('smartesb.enable_events_deferring', true);

        $dispatcher = new EventDispatcher($container);
        $dispatcher->dispatch('test_event.deferred', $event);

        $messages = $queueDriver->getArrayForQueue('test_queue');

        $this->assertCount(0, $messages);
    }

    public function tearDown()
    {
        parent::tearDown();
        ArrayQueueDriver::$array = [];
    }
}
