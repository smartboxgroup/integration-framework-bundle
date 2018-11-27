<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Processors\ControlFlow;

use Smartbox\CoreBundle\Tests\Fixtures\Entity\TestEntity;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow\ThrowException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Exceptions\Deprecated\BadRequestException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ThrowExceptionTest.
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow\ThrowException
 */
class ThrowExceptionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThrowException */
    private $throwException;

    protected function setUp()
    {
        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcherMock */
        $eventDispatcherMock = $this->createMock(EventDispatcher::class);

        $this->throwException = new ThrowException();
        $this->throwException->setEventDispatcher($eventDispatcherMock);
    }

    protected function tearDown()
    {
        $this->throwException = null;
    }

    public function invalidExceptionClassesProvider()
    {
        return [
            [null],
            [123],
            [Processor::class],
        ];
    }

    /**
     * @covers ::setExceptionClass
     */
    public function testSetExceptionClassOK()
    {
        $this->throwException->setExceptionClass(BadRequestException::class);
        $this->assertEquals(BadRequestException::class, $this->throwException->getExceptionClass());
    }

    /**
     * @dataProvider invalidExceptionClassesProvider
     * @covers ::setExceptionClass
     */
    public function testSetExceptionClassInvalid($class)
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->throwException->setExceptionClass($class);
    }

    /**
     * @covers ::doProcess
     */
    public function testProcessOK()
    {
        $this->expectException(BadRequestException::class);

        $this->throwException->setExceptionClass(BadRequestException::class);

        $ex = new Exchange(new Message(new TestEntity()));

        try {
            $this->throwException->process($ex);
        } catch (ProcessingException $pe) {
            throw $pe->getOriginalException();
        }
    }
}
