<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Events;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Events\ProcessingErrorEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ProcessingErrorEventTest.
 */
class ProcessingErrorEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Smartbox\Integration\FrameworkBundle\Events\ProcessingErrorEvent */
    private $event;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Exchange */
    private $exchange;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Processor */
    private $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Exception */
    private $exception;

    /** @var string */
    private $name;

    protected function setUp()
    {
        $this->processor = $this->createMock(Processor::class);
        $this->exchange = $this->createMock(Exchange::class);
        $this->exception = $this->createMock(\Exception::class);
        $this->name = 'some_name';

        $this->event = new ProcessingErrorEvent($this->processor, $this->exchange, $this->exception, $this->name);
        $this->event->setId(uniqid('', true));
        $this->event->setTimestampToCurrent();
    }

    protected function tearDown()
    {
        $this->processor = null;
        $this->exchange = null;
        $this->exception = null;
        $this->name = null;
        $this->event = null;
    }

    public function testItShouldBeConstructedWithAnExchange()
    {
        $this->assertSame($this->exchange, $this->event->getExchange());
    }

    public function testItShouldBeConstructedWithAProcessor()
    {
        $this->assertSame($this->processor, $this->event->getProcessor());
    }

    public function testItShouldBeConstructedWithAnException()
    {
        $this->assertSame($this->exception, $this->event->getException());
    }

    public function testItShouldBeConstructedWithAName()
    {
        $this->assertEquals($this->name, $this->event->getEventName());
    }

    public function testItShouldSetAndGetARequestStack()
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $this->event->setRequestStack($requestStack);
        $this->assertEquals($requestStack, $this->event->getRequestStack());
    }
}
