<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Traits;

use JMS\Serializer\SerializerInterface;

/**
 * Class UsesSerializerTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Processors\Traits
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer
 */
class UsesSerializerTest extends \PHPUnit_Framework_TestCase
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
     * @covers ::setSerializer
     * @covers ::getSerializer
     */
    public function testSetAndGetSerializer()
    {
        /** @var SerializerInterface|\PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->getMock(SerializerInterface::class);

        $this->fakeObject->setSerializer($serializer);

        $this->assertSame($serializer, $this->fakeObject->getSerializer());
    }
}