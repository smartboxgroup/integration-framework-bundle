<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Messages;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\String;
use Smartbox\Integration\FrameworkBundle\Messages\Context;

/**
 * Class ContextTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Messages
 */
class ContextTest extends \PHPUnit_Framework_TestCase
{

    public function testItShouldBeConstructedWithASerializableArray()
    {
        $values = new SerializableArray([
            Context::USER => new String('bar')
        ]);

        $context = new Context($values);
        $this->assertEquals('bar', $context->get(Context::USER));
    }

    public function testItShouldBeConstructedWithAnArray()
    {
        $values = [Context::USER => 'bar'];

        $context = new Context($values);
        $this->assertEquals('bar', $context->get(Context::USER));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testItShouldNotBeConstructedWithOtherThings()
    {
        new Context(new \stdClass());
    }
}
