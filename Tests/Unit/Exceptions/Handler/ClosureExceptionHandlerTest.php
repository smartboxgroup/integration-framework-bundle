<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Exceptions\Handler;

use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\ClosureExceptionHandler;

class ClosureExceptionHandlerTest extends TestCase
{
    public function testCallsClosure()
    {
        $called = false;
        $closure = function ($exception) use (&$called) {
            if ('i am exception' === $exception->getMessage()) {
                $called = true;
            }
        };

        $handler = new ClosureExceptionHandler($closure);
        $handler(new \Exception('i am exception'));

        $this->assertTrue($called, 'The closure was not called with the correct message.');
    }
}
