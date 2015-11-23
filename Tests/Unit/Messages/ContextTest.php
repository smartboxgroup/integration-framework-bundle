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
            'foo' => new String('bar')
        ]);

        $context = new Context($values);
        $this->assertEquals('bar', $context->get('foo'));
    }

    public function testItShouldBeConstructedWithAnArray()
    {
        $values = ['foo' => 'bar'];

        $context = new Context($values);
        $this->assertEquals('bar', $context->get('foo'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testItShouldNotBeConstructedWithOtherThings()
    {
        new Context(new \stdClass());
    }
}
