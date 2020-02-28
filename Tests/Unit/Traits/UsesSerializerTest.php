<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Traits;

use JMS\Serializer\SerializerInterface;

/**
 * Class UsesSerializerTest.
 *
 * @coversDefaultClass \Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer
 */
class UsesSerializerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FakeTraitsUsage
     */
    private $fakeObject;

    protected function setUp(): void
    {
        $this->fakeObject = new FakeTraitsUsage();
    }

    /**
     * @covers ::setSerializer
     * @covers ::getSerializer
     */
    public function testSetAndGetSerializer()
    {
        /** @var SerializerInterface|\PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $this->fakeObject->setSerializer($serializer);

        $this->assertSame($serializer, $this->fakeObject->getSerializer());
    }
}
