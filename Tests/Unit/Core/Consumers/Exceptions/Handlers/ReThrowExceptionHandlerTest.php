<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Consumers\Exceptions\Handlers;

use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\Exceptions\Handlers\ReThrowExceptionHandler;
use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableException;

class ReThrowExceptionHandlerTest extends TestCase
{
    public function testReThrow()
    {
        $this->expectException(RecoverableException::class);

        $handler = new ReThrowExceptionHandler();
        $handler->handle(new RecoverableException(), null);
    }
}
