<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Messages;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\StringType;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;

/**
 * Class ContextTest.
 */
class ContextTest extends \PHPUnit\Framework\TestCase
{
    public function testItShouldBeConstructedWithASerializableArray()
    {
        $values = new SerializableArray([
            Context::ORIGINAL_FROM => new StringType('bar'),
        ]);

        $context = new Context($values);
        $this->assertEquals('bar', $context->get(Context::ORIGINAL_FROM));
    }

    public function testItShouldBeConstructedWithAnArray()
    {
        $values = [Context::ORIGINAL_FROM => 'bar'];

        $context = new Context($values);
        $this->assertEquals('bar', $context->get(Context::ORIGINAL_FROM));
    }

    public function testItShouldNotBeConstructedWithOtherThings()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Context(new \stdClass());
    }
}
