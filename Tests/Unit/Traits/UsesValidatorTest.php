<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Traits;

use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UsesValidatorTest.
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesValidator
 */
class UsesValidatorTest extends \PHPUnit\Framework\TestCase
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
     * @covers ::setValidator
     * @covers ::getValidator
     */
    public function testSetAndGetValidator()
    {
        /** @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(ValidatorInterface::class);

        $this->fakeObject->setValidator($serializer);

        $this->assertSame($serializer, $this->fakeObject->getValidator());
    }
}
