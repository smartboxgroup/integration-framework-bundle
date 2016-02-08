<?php

namespace Smartbox\Integration\FrameworkBundle\Consumers;


use Smartbox\Integration\FrameworkBundle\Handlers\MessageHandler;

interface UsesMessageHandlerInterface {

    /**
     * @param MessageHandler $handler
     */
    public function setHandler(MessageHandler $handler);

    /**
     * @return MessageHandler
     */
    public function getHandler();

}