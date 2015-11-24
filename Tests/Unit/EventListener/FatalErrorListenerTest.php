<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\EventListener;

use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Events\FakeErrorEvent;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Processors\FakeProcessor;
use Smartbox\Integration\FrameworkBundle\EventListener\FatalErrorListener;

/**
 * Class FatalErrorListenerTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\EventListener
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\EventListener\FatalErrorListener
 */
class FatalErrorListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FatalErrorListener
     */
    private $listener;

    public function setUp()
    {
        $this->listener = new FatalErrorListener();
    }

    /**
     * @covers ::onErrorEvent
     */
    public function testOnErrorEventThrowsException()
    {
        $this->setExpectedException(\RuntimeException::class);

        $event = new FakeErrorEvent(new FakeProcessor('id'),new Exchange(), new \RuntimeException('test'));
        $event->mustThrowException();

        $this->listener->onErrorEvent($event);
    }

    /**
     * @covers ::onErrorEvent
     */
    public function testOnErrorEvent()
    {
        $event = new FakeErrorEvent(new FakeProcessor('id'),new Exchange(), new \RuntimeException('test'));
        $event->mustNotThrowException();
        $this->assertNull($this->listener->onErrorEvent($event));
    }
}