<?php

namespace Smartbox\Integration\FrameworkBundle\Handlers;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\Traits\HasType;
use Smartbox\Integration\FrameworkBundle\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Messages\EventMessage;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Traits\FlowsVersionAware;
use Smartbox\Integration\FrameworkBundle\Traits\MessageFactoryAware;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEventDispatcher;
use Smartbox\Integration\FrameworkBundle\Helper\SlugHelper;

/**
 * Class DeferredEventsHandler
 * @package Smartbox\Integration\FrameworkBundle\Handlers
 */
class DeferredEventsHandler implements HandlerInterface
{
    use HasType;
    use UsesEventDispatcher;
    use FlowsVersionAware;

    /**
     * @param MessageInterface $message
     * @return MessageInterface
     * @throws \Exception
     */
    public function handle(MessageInterface $message)
    {
        if(!$message instanceof EventMessage){
            throw new \InvalidArgumentException("Expected EventMessage as an argument");
        }

        $version = $message->getContext()->get(Context::VERSION);
        $expectedVersion = $this->getFlowsVersion();

        if($version !== $expectedVersion){
            throw new \Exception(
                sprintf(
                    'Received message with wrong version in deferred events handler. Expected: %s, received: %s',
                    $expectedVersion,
                    $version
                )
            );
        }

        $this->eventDispatcher->dispatch($message->getHeader(EventMessage::HEADER_EVENT_NAME).'.deferred', $message->getBody());

        return $message;
    }

}