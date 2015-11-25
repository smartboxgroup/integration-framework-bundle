<?php

namespace Smartbox\Integration\FrameworkBundle\Events;

use Smartbox\Integration\FrameworkBundle\Messages\EventMessage;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * Class EventDispatcher
 * @package Smartbox\Integration\FrameworkBundle\Events
 */
class EventDispatcher extends ContainerAwareEventDispatcher{

    public function shouldDefer(\Symfony\Component\EventDispatcher\Event $event){
        if($event instanceof \Smartbox\Integration\FrameworkBundle\Events\Event){

            $filtersRegistry = $this->getContainer()->get('smartesb.registry.event_filters');
            $filters = $filtersRegistry->getDeferringFilters();

            /** @var EventFilterInterface $filter */
            foreach($filters as $filter){
                if($filter->filter($event)){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Store logs in the queue
     *
     * @param Event $event
     */
    public function enqueue(Event $event)
    {
        $queueDriver = $this->getContainer()->get('smartesb.queue.driver.events');
        $queueName = $this->getContainer()->getParameter('smartesb.events_queue_name');

        if(!$queueDriver->isConnected()){
            $queueDriver->connect();
        }

        $message = $queueDriver->createQueueMessage();
        $message->setBody(new EventMessage($event));
        $message->setQueue($queueName);

        $queueDriver->send($message);
    }

    /** {@inheritdoc} */
    public function dispatch($eventName, \Symfony\Component\EventDispatcher\Event $event = null)
    {
        parent::dispatch($eventName,$event);

        $isDeferred = strpos($eventName,'.deferred') !==  false;

        if(!$isDeferred && $this->shouldDefer($event)){
            $this->enqueue($event);
        }
    }
}
