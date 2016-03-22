<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Processors\ControlFlow;

use Smartbox\CoreBundle\Tests\Fixtures\Entity\TestEntity;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow\ThrowException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Exceptions\Deprecated\InvalidMessageException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ThrowExceptionTest.
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow\ThrowException
 */
class ThrowExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ThrowException
     */
    private $throwException;

    public function setUp()
    {
        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcherMock */
        $eventDispatcherMock = $this->getMock(EventDispatcher::class);

        $this->throwException = new ThrowException();
        $this->throwException->setEventDispatcher($eventDispatcherMock);
    }

    public function invalidExceptionClassesProvider()
    {
        return array(
            array(null),
            array(123),
            array(Processor::class),
        );
    }

    /**
     * @covers ::setExceptionClass
     */
    public function testSetExceptionClassOK()
    {
        $this->throwException->setExceptionClass(InvalidMessageException::class);
        $this->assertEquals(InvalidMessageException::class, $this->throwException->getExceptionClass());
    }

    /**
     * @dataProvider invalidExceptionClassesProvider
     * @covers ::setExceptionClass
     */
    public function testSetExceptionClassInvalid($class)
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->throwException->setExceptionClass($class);
    }

    /**
     * @covers ::doProcess
     */
    public function testProcessOK()
    {
        $this->setExpectedException(InvalidMessageException::class);
        $this->throwException->setExceptionClass(InvalidMessageException::class);

        $ex = new Exchange(new Message(new TestEntity()));

        try {
            $this->throwException->process($ex);
        } catch (ProcessingException $pe) {
            throw $pe->getOriginalException();
        }
    }
}
