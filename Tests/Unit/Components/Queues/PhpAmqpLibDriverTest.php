<?php

namespace Smartbox\Integration\FrameworkBundle\Tests;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\PhpAmqpLibDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\Drivers\Queue\AbstractQueueDriverTest;

/**
 * Class PhpAmqpLibDriverTest
 * @package Smartbox\Integration\FrameworkBundle\Tests
 * @group php-amqp-lib
 */
class PhpAmqpLibDriverTest extends AbstractQueueDriverTest
{
    /**
     * @var PhpAmqpLibDriver
     */
    protected $driver;

    public function setUp()
    {
        $this->driver = $this->createDriver();
        $this->driver->setPort(QueueDriverInterface::DEFAULT_PORT);
        $this->driver->configure(
            'rabbit',
            'guest',
            'guest',
            'test'
        );
        $this->driver->connect();
    }

    /**
     * @inheritDoc
     */
    protected function createDriver(): QueueDriverInterface
    {
        return new PhpAmqpLibDriver();
    }

    /**
     * @dataProvider getMessages
     *
     * @param MessageInterface $msg
     * @throws \Exception
     * @group php1
     */
    public function testShouldSelect(MessageInterface $msg)
    {
        $msgIn = $this->createQueueMessage($msg);
        $msgIn->addHeader('test_header', '12345');
        $this->driver->send($msgIn);

//        $this->driver->subscribe($this->queueName, 'test_header = 12345');

        $this->assertNotNull($this->driver->receive(), 'Message should be received');
        $this->driver->ack();
        $this->driver->unSubscribe();
    }

    /**
     * @throws \Exception
     * @group send
     */
//    public function testSend()
//    {
//        $msg = $this->createQueueMessage($this->createSimpleEntity('item1'));
//        $this->driver->send($msg);
//    }

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


    public function testGetFormat()
    {

    }

}
