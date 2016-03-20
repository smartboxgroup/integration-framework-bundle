<?php


namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Events;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\ArrayQueueDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\SmartboxIntegrationFrameworkExtension;
use Smartbox\Integration\FrameworkBundle\Events\HandlerEvent;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageFactory;
use Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventDispatcher;
use Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventFiltersRegistry;
use Smartbox\Integration\FrameworkBundle\Tools\Helper\SmartesbHelper;
use Symfony\Component\DependencyInjection\Container;

class EventDispatcherTest extends \PHPUnit_Framework_TestCase{

    public function testShouldDeferEvent(){
        $filterPass = $this->getMock(\Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventFilterInterface::class);
        $filterPass->method('filter')->willReturn(true);

        $filtersRegistry = new \Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventFiltersRegistry();
        $filtersRegistry->addDeferringFilter($filterPass);

        $messageFactory = new MessageFactory();
        $messageFactory->setFlowsVersion(0);

        $queueDriver = new ArrayQueueDriver();
        $queueDriver->setMessageFactory($messageFactory);

        $event = new HandlerEvent();
        $event->setTimestampToCurrent();
        $helper = new SmartesbHelper();

        $container = new Container();
        $container->set('smartesb.registry.event_filters',$filtersRegistry);
        $container->set(SmartboxIntegrationFrameworkExtension::QUEUE_DRIVER_PREFIX.'events',$queueDriver);
        $container->set('smartesb.helper',$helper);
        $container->setParameter('smartesb.flows_version', '0');

        $helper->setContainer($container);

        $dispatcher = new \Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventDispatcher($container);
        $dispatcher->dispatch("test_event", $event);

        $messages = $queueDriver->getArrayForQueue('test_queue');

        $this->assertCount(1,$messages);
        /** @var QueueMessage $message */
        $message = $messages[0];

        $this->assertInstanceOf(QueueMessage::class,$message);

        $this->assertInstanceOf(\Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventMessage::class,$message->getBody());

        $this->assertEquals($message->getBody()->getBody(),$event);
    }


    public function testShouldNotDeferEventIfDeferred(){
        $filterPass = $this->getMock(\Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventFilterInterface::class);
        $filterPass->method('filter')->willReturn(true);

        $filtersRegistry = new EventFiltersRegistry();
        $filtersRegistry->addDeferringFilter($filterPass);

        $queueDriver = new ArrayQueueDriver();

        $event = new HandlerEvent();
        $event->setTimestampToCurrent();

        $container = new Container();
        $container->set('smartesb.registry.event_filters',$filtersRegistry);
        $container->set(SmartboxIntegrationFrameworkExtension::QUEUE_DRIVER_PREFIX.'events',$queueDriver);

        $dispatcher = new EventDispatcher($container);
        $dispatcher->dispatch("test_event.deferred", $event);

        $messages = $queueDriver->getArrayForQueue('test_queue');

        $this->assertCount(0,$messages);
    }


    public function testShouldNotDeferEventIfDoesNotPassFilter(){
        $filterDoesNotPass = $this->getMock(
            \Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventFilterInterface::class);
        $filterDoesNotPass->method('filter')->willReturn(false);

        $filtersRegistry = new EventFiltersRegistry();
        $filtersRegistry->addDeferringFilter($filterDoesNotPass);

        $queueDriver = new ArrayQueueDriver();

        $event = new HandlerEvent();
        $event->setTimestampToCurrent();

        $container = new Container();
        $container->set('smartesb.registry.event_filters',$filtersRegistry);
        $container->set(SmartboxIntegrationFrameworkExtension::QUEUE_DRIVER_PREFIX.'events',$queueDriver);

        $dispatcher = new \Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventDispatcher($container);
        $dispatcher->dispatch("test_event.deferred", $event);

        $messages = $queueDriver->getArrayForQueue('test_queue');

        $this->assertCount(0,$messages);
    }

    public function tearDown(){
        parent::tearDown();
        ArrayQueueDriver::$array = array();
    }

}