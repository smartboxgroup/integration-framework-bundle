<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Handlers;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerException;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

class MessageHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var MessageHandler */
    private $messageHandler;

    protected function setUp()
    {
        $this->messageHandler = new MessageHandler;
    }

    protected function tearDown()
    {
        $this->messageHandler = null;
    }

    public function testSetAndShouldDeferNewExchanges()
    {
        $this->messageHandler->setDeferNewExchanges(true);

        $this->assertTrue($this->messageHandler->shouldDeferNewExchanges());
    }

    public function testSetAndGetRetriesMax()
    {
        $retriesMax = 5;

        $this->messageHandler->setRetriesMax($retriesMax);

        $this->assertSame($retriesMax, $this->messageHandler->getRetriesMax());
    }

    public function testSetAndGetRetryDelay()
    {
        $retryDelay = 1000;

        $this->messageHandler->setRetryDelay($retryDelay);

        $this->assertSame($retryDelay, $this->messageHandler->getRetryDelay());
    }

    public function testProcessExchangeIfItineraryIsNotFound()
    {
        $this->expectException(HandlerException::class);

        $messageInterface = $this->createMock(MessageInterface::class);

        $exchange = new Exchange($messageInterface);

        $this->messageHandler->processExchange($exchange);
    }
}