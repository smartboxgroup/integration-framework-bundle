<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Exceptions\Handler;

use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\ReThrowExceptionHandler;
use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableException;

class ReThrowExceptionHandlerTest extends TestCase
{
    public function testReThrow()
    {
        $this->expectException(RecoverableException::class);

        $endpoint = $this->createMock(Endpoint::class);

        $handler = new ReThrowExceptionHandler();
        $handler(new RecoverableException(), $endpoint, null);
    }
}
