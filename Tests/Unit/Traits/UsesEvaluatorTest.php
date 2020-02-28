<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Traits;

use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator;

/**
 * Class UsesEvaluatorTest.
 *
 * @coversDefaultClass \Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator
 */
class UsesEvaluatorTest extends \PHPUnit\Framework\TestCase
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
     * @covers ::setEvaluator
     * @covers ::getEvaluator
     */
    public function testSetAndGetEvaluator()
    {
        /** @var ExpressionEvaluator|\PHPUnit_Framework_MockObject_MockObject $evaluatorMock */
        $evaluatorMock = $this->createMock(ExpressionEvaluator::class);

        $this->fakeObject->setEvaluator($evaluatorMock);

        $this->assertSame($evaluatorMock, $this->fakeObject->getEvaluator());
    }
}
