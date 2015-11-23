<?php

namespace Smartbox\Integration\FrameworkBundle\Events;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasType;
use Symfony\Component\EventDispatcher\Event as BaseEvent;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Event
 * @package Smartbox\Integration\FrameworkBundle\Events
 */
abstract class Event extends BaseEvent implements SerializableInterface
{
    use HasType;

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("DateTime")
     * @var \DateTime
     */
    protected $timestamp;

    /**
     * @var string
     */
    protected $eventName;

    /**
     * Constructor
     */
    public function __construct($eventName = null)
    {
        $this->setName($eventName);
        $this->eventName = $eventName;
        $this->timestamp = new \DateTime();
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param \DateTime $timestamp
     */
    public function setTimestamp(\DateTime $timestamp = null)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * @param string $eventName
     */
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;
    }
}
