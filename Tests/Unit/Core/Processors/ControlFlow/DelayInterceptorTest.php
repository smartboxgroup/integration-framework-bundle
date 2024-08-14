<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Processors\ControlFlow;

use Smartbox\CoreBundle\Tests\Fixtures\Entity\TestEntity;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow\DelayInterceptor;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\DelayException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ProcessingException;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class DelayInterceptorTest.
 *
 * @coversDefaultClass \Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow\DelayInterceptorTest
 */
class DelayInterceptorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test that when we process a message that shall not pass and the processor is asyncDelayed that we do throw an
     * Exception and that exception has a delay set.
     */
    public function testExceptionsDelayIsSet()
    {
        $throttlerMock = $this->getMockBuilder(DelayInterceptor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcherMock = $this->createMock(EventDispatcher::class);
        $throttlerMock->setEventDispatcher($eventDispatcherMock);
        $throttlerMock->expects($this->any())
            ->method('getEventDispatcher')
            ->will($this->returnValue($eventDispatcherMock));

        $exchange = new Exchange(new Message(null, ['delay' => 10], null));

        //We do not use expectException, instead we want to actually inspect what is in the exception
        $thrown = false;
        try {
            $throttlerMock->process($exchange);
        } catch (\Exception $e) {
            $thrown = true;
            $this->assertInstanceOf(ProcessingException::class, $e);
            $this->assertInstanceOf(DelayException::class, $e->getOriginalException());
        }

        $this->assertTrue($thrown, 'Process did not throw an exception');
    }
}
