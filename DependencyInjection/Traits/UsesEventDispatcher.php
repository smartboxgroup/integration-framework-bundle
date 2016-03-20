<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

trait UsesEventDispatcher
{
    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @return EventDispatcherInterface|null
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }
}