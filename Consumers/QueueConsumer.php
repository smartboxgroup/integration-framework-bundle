<?php

namespace Smartbox\Integration\FrameworkBundle\Consumers;

use Smartbox\Integration\FrameworkBundle\Drivers\Queue\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Handlers\HandlerInterface;
use CentralDesktop\Stomp\Exception as StompException;
use Smartbox\Integration\FrameworkBundle\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Messages\Queues\QueueMessage;

/**
 * Class QueueConsumer
 * @package Smartbox\Integration\FrameworkBundle\Consumers
 */
class QueueConsumer extends AbstractQueueConsumer implements QueueConsumerInterface, UsesMessageHandlerInterface
{
    use UsesMessageHandler;

    protected function process(QueueMessage $message)
    {
        $this->getHandler()->handle($message->getBody(),$message->getDestinationURI());
    }
}
