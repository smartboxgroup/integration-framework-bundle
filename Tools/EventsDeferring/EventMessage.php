<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Events\Event;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class EventMessage.
 */
class EventMessage extends Message
{
    const HEADER_EVENT_NAME = 'event_name';

    /**
     * @Assert\Valid(traverse=true, deep=true)
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Events\Event")
     * @JMS\Groups({"logs"})
     * @JMS\Expose
     *
     * @var Event
     */
    protected $body;

    /**
     * @return Event
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param SerializableInterface $event
     */
    public function setBody(SerializableInterface $event = null)
    {
        if (!$event instanceof Event) {
            throw new \InvalidArgumentException('Expected Event as parameter');
        }

        if ($event->getEventName()) {
            $this->addHeader(self::HEADER_EVENT_NAME, $event->getEventName());
        }

        $this->body = $event;
    }
}
