<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Exceptions\Handler;

use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\ClosureExceptionHandler;

class ClosureExceptionHandlerTest extends TestCase
{
    public function testCallsClosure()
    {
        $called = false;
        $closure = function ($exception, $context) use (&$called) {
            if ('i am exception' === $exception->getMessage()) {
                $called = $context;
            }
        };

        $handler = new ClosureExceptionHandler($closure);
        $handler(new \Exception('i am exception'), 'i am context');

        $this->assertNotFalse($called, 'The closure was not called with the correct message.');
        $this->assertSame($called, 'i am context', 'The closure should have been able to use the context');
    }
}
