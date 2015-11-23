<?php

namespace Smartbox\Integration\FrameworkBundle\Handlers;

use Smartbox\Integration\FrameworkBundle\Connectors\Connector;
use Smartbox\Integration\FrameworkBundle\Connectors\ConnectorInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Processors\Itinerary;

interface HandlerInterface
{
    /**
     * @param MessageInterface $message
     * @return MessageInterface
     */
    public function handle(MessageInterface $message);
}