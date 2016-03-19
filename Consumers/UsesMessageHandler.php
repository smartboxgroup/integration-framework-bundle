<?php

namespace Smartbox\Integration\FrameworkBundle\Consumers;


use Smartbox\Integration\FrameworkBundle\Handlers\MessageHandler;

trait UsesMessageHandler {

    /** @var  MessageHandler */
    protected $handler;

    /**
     * @param MessageHandler $handler
     */
    public function setHandler(MessageHandler $handler){
        $this->handler = $handler;
    }
}