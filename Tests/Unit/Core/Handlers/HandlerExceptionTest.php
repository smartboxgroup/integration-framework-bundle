<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Handlers;

use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerException;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

class HandlerExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        /** @var $messageInterface $messageInterface */
        $messageInterface = $this->createMock(MessageInterface::class);
        $message = 'Handler exception';

        $handlerException = new HandlerException($message, $messageInterface);

        $this->assertSame($message, $handlerException->getMessage());
        $this->assertSame($messageInterface, $handlerException->getFailedMessage());
    }

    public function testSetAndGetFailedMessage()
    {
        /** @var $messageInterface $messageInterface */
        $messageInterface = $this->createMock(MessageInterface::class);
        $failedMessage = $this->createMock(MessageInterface::class);

        $handlerException = new HandlerException('', $messageInterface);
        $handlerException->setFailedMessage($failedMessage);

        $this->assertSame($failedMessage, $handlerException->getFailedMessage());
    }
}
