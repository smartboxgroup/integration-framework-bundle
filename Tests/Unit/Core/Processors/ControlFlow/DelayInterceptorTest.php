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
 * @coversDefaultClass \Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow\DelayInterceptor
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
        $throttlerMock->method('getEventDispatcher')
            ->willReturn($eventDispatcherMock);

        $message = new Message(new TestEntity());
        $message->setHeader('delay', 10);

        $exchange = new Exchange($message);

        //We do not use expectException, instead we want to actually inspect what is in the exception
        try {
            $throttlerMock->process($exchange);
        } catch (\Exception $e) {
            $this->assertInstanceOf(ProcessingException::class, $e);
            $this->assertInstanceOf(DelayException::class, $e->getOriginalException());
            return;
        }

        $this->fail('Process did not throw an exception');
    }
}
