<?php

namespace Smartbox\Integration\FrameworkBundle\Handlers;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Messages\EventMessage;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Traits\FlowsVersionAware;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEventDispatcher;

/**
 * Class DeferredEventsHandler
 * @package Smartbox\Integration\FrameworkBundle\Handlers
 */
class DeferredEventsHandler implements HandlerInterface
{
    use HasInternalType;
    use UsesEventDispatcher;
    use FlowsVersionAware;

    /**
     * {@inheritDoc}
     */
    public function handle(MessageInterface $message, EndpointInterface $endpoint)
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