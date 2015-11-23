<?php

namespace Smartbox\Integration\FrameworkBundle\Events;



interface EventFilterInterface {

    /**
     * @param Event $event
     * @return boolean
     */
    public function filter(Event $event);

}