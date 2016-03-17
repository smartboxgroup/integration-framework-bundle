<?php

namespace Smartbox\Integration\FrameworkBundle\Handlers;

use Smartbox\Integration\FrameworkBundle\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\Producers\ProducerInterface;
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