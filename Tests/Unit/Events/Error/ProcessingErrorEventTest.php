<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Events\Error;

use Smartbox\Integration\FrameworkBundle\Events\Error\ProcessingErrorEvent;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Processors\Processor;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ProcessingErrorEventTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Events\Error
 */
class ProcessingErrorEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProcessingErrorEvent */
    private $event;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Exchange */
    private $exchange;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Processor */
    private $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Exception */
    private $exception;

    /** @var  string */
    private $name;

    public function setup()
    {
        $this->processor = $this->getMock(Processor::class);
        $this->exchange = $this->getMock(Exchange::class);
        $this->exception = $this->getMock(\Exception::class);
        $this->name = 'some_name';

        $this->event = new ProcessingErrorEvent($this->processor, $this->exchange, $this->exception, $this->name);
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

    public function testItShouldSpecifyWhetherToThrowAnException()
    {
        $this->event->mustThrowException();
        $this->assertTrue($this->event->shouldThrowException());

        $this->event->mustNotThrowException();
        $this->assertFalse($this->event->shouldThrowException());
    }

    public function testItShouldSetAndGetARequestStack()
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->getMock(RequestStack::class);
        $this->event->setRequestStack($requestStack);
        $this->assertEquals($requestStack, $this->event->getRequestStack());
    }

}
