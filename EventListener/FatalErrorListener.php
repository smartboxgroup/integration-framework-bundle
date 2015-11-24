<?php

namespace Smartbox\Integration\FrameworkBundle\EventListener;

use Smartbox\Integration\FrameworkBundle\Events\Error\ProcessingErrorEvent;

/**
 * Class FatalErrorListener
 * @package Smartbox\Integration\FrameworkBundle\EventListener
 */
class FatalErrorListener
{
    public function onErrorEvent(ProcessingErrorEvent $event)
    {
        if ($event->shouldThrowException()){
            throw $event->getException();
        }
    }
}