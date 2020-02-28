<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Event;

use Smartbox\Integration\FrameworkBundle\Events\Event;

/**
 * Class EventTest.
 */
class EventTest extends \PHPUnit\Framework\TestCase
{
    /** @var Event|\PHPUnit_Framework_MockObject_MockObject */
    private $event;

    public function setup()
    {
        $this->event = $this->getMockBuilder(Event::class)
            ->enableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
    }

    public function testItShouldBeConstructedWithAnOptionalEventName()
    {
        $this->assertNull($this->event->getEventName());

        $this->event = $this->getMockBuilder(Event::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs(['some_name'])
            ->getMockForAbstractClass()
        ;

        $this->assertEquals('some_name', $this->event->getEventName());
    }

    public function testItShouldSetAndGetAnEventName()
    {
        $this->event->setEventName('some_name');
        $this->assertEquals('some_name', $this->event->getEventName());
    }

    public function testItShouldSetAndGetATimestamp()
    {
        $timestamp = new \DateTime();
        $this->event->setTimestamp($timestamp);
        $this->assertSame($timestamp, $this->event->getTimestamp());
    }
}
