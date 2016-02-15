<?php

namespace Smartbox\Integration\FrameworkBundle\Consumers;


use Smartbox\Integration\FrameworkBundle\Handlers\DeferredEventsHandler;

interface UsesDeferredEventsHandlerInterface {

    /**
     * @param DeferredEventsHandler $handler
     */
    public function setHandler(DeferredEventsHandler $handler);

    /**
     * @return DeferredEventsHandler
     */
    public function getHandler();
}