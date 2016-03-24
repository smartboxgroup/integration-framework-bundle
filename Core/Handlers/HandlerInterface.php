<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Handlers;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

/**
 * Interface HandlerInterface.
 */
interface HandlerInterface
{
    /**
     * Handles a message coming from an Endpoint.
     *
     * To ensure the Message Delivery Guarantee, it's important to throw an Exception if anything wrong happens and the
     * message can't be handled as it's supposed to be, otherwise the message will be consumed as if it would have been
     * properly handled.
     *
     * @param MessageInterface  $message
     * @param EndpointInterface $endpoint
     *
     * @return MessageInterface
     *
     * @throws \Exception
     */
    public function handle(MessageInterface $message, EndpointInterface $endpoint);
}
