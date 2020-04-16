<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\DependencyInjection\Traits;

use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\ReThrowExceptionHandler;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesDecodingExceptionHandler;

class UsesDecodingExceptionHandlerTest extends TestCase
{
    public function testUsesReThrowByDefault()
    {
        /** @var UsesDecodingExceptionHandler $mock */
        $mock = $this->getMockForTrait(UsesDecodingExceptionHandler::class);

        $this->assertInstanceOf(ReThrowExceptionHandler::class, $mock->getDecodingExceptionHandler());
    }
}
