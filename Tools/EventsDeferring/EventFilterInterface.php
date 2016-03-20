<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring;



use Smartbox\Integration\FrameworkBundle\Events\Event;

interface EventFilterInterface {

    /**
     * @param Event $event
     * @return boolean
     */
    public function filter(Event $event);

}