<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring;

use Smartbox\Integration\FrameworkBundle\Events\Event;

/**
 * Interface EventFilterInterface.
 */
interface EventFilterInterface
{
    /**
     * @param Event $event
     *
     * @return bool
     */
    public function filter(Event $event);
}
