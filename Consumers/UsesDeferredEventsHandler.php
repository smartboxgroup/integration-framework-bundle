<?php

namespace Smartbox\Integration\FrameworkBundle\Consumers;


use Smartbox\Integration\FrameworkBundle\Handlers\DeferredEventsHandler;
use Smartbox\Integration\FrameworkBundle\Handlers\MessageHandler;

trait UsesDeferredEventsHandler {
    /** @var  DeferredEventsHandler */
    protected $handler;

    /**
     * @param DeferredEventsHandler $handler
     */
    public function setHandler(DeferredEventsHandler $handler){
        $this->handler = $handler;
    }

    /**
     * @return MessageHandler
     */
    public function getHandler(){
        return $this->handler;
    }
}