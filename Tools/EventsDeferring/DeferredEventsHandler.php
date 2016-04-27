<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring;

use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\FlowsVersionAware;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEventDispatcher;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class DeferredEventsHandler.
 */
class DeferredEventsHandler extends Service implements HandlerInterface
{
    use HasInternalType;
    use UsesEventDispatcher;
    use FlowsVersionAware;

    /**
     * {@inheritdoc}
     */
    public function handle(MessageInterface $message, EndpointInterface $endpoint)
    {
        if (!$message instanceof EventMessage) {
            throw new \InvalidArgumentException('Expected EventMessage as an argument');
        }

        $version = $message->getContext()->get(Context::VERSION);
        $expectedVersion = $this->getFlowsVersion();

        if ($version !== $expectedVersion) {
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