<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Exceptions\Handler;

use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\ReThrowExceptionHandler;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableException;

class UsesExceptionHandlerTraitTest extends TestCase
{
    public function testUsesReThrowByDefault()
    {
        /** @var UsesExceptionHandlerTrait $mock */
        $mock = $this->getMockForTrait(UsesExceptionHandlerTrait::class);

        $this->assertInstanceOf(ReThrowExceptionHandler::class, $mock->getExceptionHandler());
    }
}
