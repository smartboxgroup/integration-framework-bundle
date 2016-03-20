<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Traits;

use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator;

/**
 * Class UsesEvaluatorTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Processors\Traits
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator
 */
class UsesEvaluatorTest extends \PHPUnit_Framework_TestCase
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
     * @covers ::setEvaluator
     * @covers ::getEvaluator
     */
    public function testSetAndGetEvaluator()
    {
        /** @var ExpressionEvaluator|\PHPUnit_Framework_MockObject_MockObject $evaluatorMock */
        $evaluatorMock = $this->getMock(ExpressionEvaluator::class);

        $this->fakeObject->setEvaluator($evaluatorMock);

        $this->assertSame($evaluatorMock, $this->fakeObject->getEvaluator());
    }
}