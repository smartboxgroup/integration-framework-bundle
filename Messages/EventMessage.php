<?php

namespace Smartbox\Integration\FrameworkBundle\Messages;

use Smartbox\Integration\FrameworkBundle\Events\Event;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;

/**
 * Class EventMessage
 * @package Smartbox\Integration\FrameworkBundle\Messages
 */
class EventMessage extends Message
{
    const HEADER_EVENT_NAME = 'event_name';

    /**
     * @Assert\Valid(traverse=true, deep=true)
     * @JMS\Type("Smartbox\Integration\ServiceBusBundle\Event\Event")
     * @JMS\Groups({"logs"})
     * @JMS\Expose
     * @var Event
     */
    protected $body;

    /**
     * @return Event
     */
    public function getBody(){
        return $this->body;
    }

    /**
     * @param Event $event
     */
    public function setBody(SerializableInterface $event = null)
    {
        if (!$event instanceof Event) {
            throw new \InvalidArgumentException("Expected Event as parameter");
        }

        if($event->getEventName()){
            $this->addHeader(self::HEADER_EVENT_NAME,$event->getEventName());
        }

        $this->body = $event;
    }

}