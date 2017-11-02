<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Handlers;

use Smartbox\CoreBundle\Tests\Fixtures\Entity\TestEntity;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerException;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\ThrottledExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow\Throttler;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ThrottledException;
use Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring\EventDispatcher;

class MessageHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var MessageHandler */
    private $messageHandler;

    protected function setUp()
    {
        $this->messageHandler = new MessageHandler;
    }

    protected function tearDown()
    {
        $this->messageHandler = null;
    }

    public function testSetAndShouldDeferNewExchanges()
    {
        $this->messageHandler->setDeferNewExchanges(true);

        $this->assertTrue($this->messageHandler->shouldDeferNewExchanges());
    }

    public function testSetAndGetRetriesMax()
    {
        $retriesMax = 5;

        $this->messageHandler->setRetriesMax($retriesMax);

        $this->assertSame($retriesMax, $this->messageHandler->getRetriesMax());
    }

    public function testSetAndGetRetryDelay()
    {
        $retryDelay = 1000;

        $this->messageHandler->setRetryDelay($retryDelay);

        $this->assertSame($retryDelay, $this->messageHandler->getRetryDelay());
    }

    public function testProcessExchangeIfItineraryIsNotFound()
    {
        $this->expectException(HandlerException::class);

        $messageInterface = $this->createMock(MessageInterface::class);

        $exchange = new Exchange($messageInterface);

        $this->messageHandler->processExchange($exchange);
    }

    public function testMessageHandlerPutsThrottleExceptionsInThrottledEnvelope()
    {
        $messageHandlerMock = $this->getMockBuilder(MessageHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(array('deferRetryExchangeMessage', 'addCommonErrorHeadersToEnvelope'))
            ->getMock();

        $messageHandlerMock->setRetryStrategy(MessageHandler::RETRY_STRATEGY_PROGRESSIVE);

        $eventDispatcherMock = $this->createMock(EventDispatcher::class);
        $messageHandlerMock->setEventDispatcher($eventDispatcherMock);

        $exception = new ProcessingException();
        $exception->setOriginalException(new ThrottledException());
        $exception->setProcessingContext(new SerializableArray());
        $exception->getOriginalException()->setDelay(1);
        $exchange = new Exchange(new Message(new TestEntity()));
        $processor = new Throttler(); //this could be any processor
        $processor->setId('i-am-id');

        //Set up the test, i.e. that the onhandle sets a ThrottledExchangeEnvelope
        $messageHandlerMock->expects($this->once())
            ->method('deferRetryExchangeMessage')
            ->with($this->isInstanceOf(ThrottledExchangeEnvelope::class));

        //And now call the the Handler
        $messageHandlerMock->onHandleException($exception, $processor, $exchange, null, null);
    }
}