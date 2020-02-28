<?php

namespace Smartbox\Integration\FrameworkBundle\Tests;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\PhpAmqpLibDriver;
use PHPUnit\Framework\TestCase;

class PhpAmqpLibDriverTest extends TestCase
{
    /**
     * @var PhpAmqpLibDriver
     */
    private $driver;

    public function setUp()
    {
        $this->driver = new PhpAmqpLibDriver();
    }

    /**
     * @group phpamqplib-configure
     */
    public function testConfigure()
    {

    }

    public function testNack()
    {

    }

    public function testWaitNoBlock()
    {

    }

    public function testCreateQueueMessage()
    {

    }

    public function testWait()
    {

    }

    public function testAck()
    {

    }

    public function testDeclareExchange()
    {

    }

    public function testIsConsuming()
    {

    }

    public function testConnect()
    {

    }

    public function testConsume()
    {

    }

    public function testDestroy()
    {

    }

    public function testDeclareChannel()
    {

    }

    public function testSetFormat()
    {

    }

    public function testAddConnection()
    {

    }

    public function testIsConnected()
    {

    }

    public function testDisconnect()
    {

    }

    public function testSetPort()
    {

    }

    public function testDeclareQueue()
    {

    }

    public function testSend()
    {

    }

    public function testGetFormat()
    {

    }
}
