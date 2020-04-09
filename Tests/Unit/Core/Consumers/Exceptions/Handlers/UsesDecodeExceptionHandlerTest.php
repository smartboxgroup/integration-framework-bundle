<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Consumers\Exceptions\Handlers;

use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\Exceptions\Handlers\ReThrowExceptionHandler;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\Exceptions\Handlers\UsesDecodeExceptionHandler;

class UsesDecodeExceptionHandlerTest extends TestCase
{
    public function testUsesReThrowByDefault()
    {
        /** @var UsesDecodeExceptionHandler $mock */
        $mock = $this->getMockForTrait(UsesDecodeExceptionHandler::class);

        $this->assertInstanceOf(ReThrowExceptionHandler::class, $mock->getDecodeExceptionHandler());
    }
}
