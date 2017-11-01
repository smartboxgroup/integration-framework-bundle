<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Processors\ControlFlow;

use Smartbox\CoreBundle\Tests\Fixtures\Entity\TestEntity;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow\Throttler;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ProcessingException;
use Symfony\Component\EventDispatcher\EventDispatcher;


/**
 * Class ThrottleTest.
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow\ThrottleTest
 */
class ThrottleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * A delay used for testing
     */
    const DELAY = 3333;

    /**
     * Test that when we process a message that shall not pass and the processor is asyncDelayed that we do throw an
     * Exception and that exception has a delay set
     */
    public function testExceptionsDelayIsSet()
    {
        $throttlerMock = $this->getMockBuilder(Throttler::class)
            ->disableOriginalConstructor()
            ->setMethods(array('checkReset', 'shouldPass', 'isAsyncDelayed'))
            ->getMock();

        $throttlerMock->expects($this->once())
            ->method('checkReset');

        $throttlerMock->expects($this->once())
            ->method('shouldPass')
            ->willReturn(false);

        $throttlerMock->expects($this->once())
            ->method('isAsyncDelayed')
            ->willReturn(true);

        $eventDispatcherMock = $this->createMock(EventDispatcher::class);
        $throttlerMock->setEventDispatcher($eventDispatcherMock);

        //set the delay, then check it is set
        $throttlerMock->setDelayS(self::DELAY);
        $this->assertEquals(self::DELAY, $throttlerMock->getDelayS());

        $exchange = new Exchange(new Message(new TestEntity()));

        //We do not use expectException, instead we want to actually inspect what is in the exception
        $thrown = false;
        try{
            $throttlerMock->process($exchange);
        }
        catch(\Exception $e){
            $thrown = true;
            $this->assertInstanceOf(ProcessingException::class, $e);
            $this->assertEquals(self::DELAY, $e->getOriginalException()->getDelay());
        }

        $this->assertTrue($thrown, 'Process did not throw and exception');
    }
}
