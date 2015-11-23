<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Traits;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class UsesEventDispatcherTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Processors\Traits
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Traits\UsesEventDispatcher
 */
class UsesEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FakeTraitsUsage
     */
    private $fakeObject;

    public function setUp()
    {
        $this->fakeObject = new FakeTraitsUsage();
    }

    /**
     * @covers ::setEventDispatcher
     * @covers ::getEventDispatcher
     */
    public function testSetAndGetEventDispatcher()
    {
        /** @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject $eventDispatcherMock */
        $eventDispatcherMock = $this->getMock(EventDispatcher::class);

        $this->fakeObject->setEventDispatcher($eventDispatcherMock);

        $this->assertSame($eventDispatcherMock, $this->fakeObject->getEventDispatcher());
    }
}