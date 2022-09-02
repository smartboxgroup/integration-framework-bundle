<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Traits;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class UsesEventDispatcherTest.
 *
 * @coversDefaultClass \Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEventDispatcher
 */
class UsesEventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FakeTraitsUsage
     */
    private $fakeObject;

    public function setUp(): void
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
        $eventDispatcherMock = $this->createMock(EventDispatcher::class);

        $this->fakeObject->setEventDispatcher($eventDispatcherMock);

        $this->assertSame($eventDispatcherMock, $this->fakeObject->getEventDispatcher());
    }
}
