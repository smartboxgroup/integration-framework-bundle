<?php

namespace Smartbox\Integration\FrameworkBundle\Events;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Symfony\Component\EventDispatcher\Event as BaseEvent;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Event.
 */
abstract class Event extends BaseEvent implements SerializableInterface
{
    use HasInternalType;

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("DateTime<'Y-m-d\TH:i:s.uP'>")
     *
     * @var \DateTime
     */
    protected $timestamp;

    /**
     * @var string
     */
    protected $eventName;

    /**
     * Constructor.
     *
     * @param string|null $eventName
     */
    public function __construct($eventName = null)
    {
        $this->setName($eventName);
        $this->eventName = $eventName;
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

    public function setTimestampToCurrent()
    {
        // Generates the date time including microseconds correctly
        $t = microtime(true);
        $micro = sprintf('%06d', ($t - floor($t)) * 1000000);
        $this->setTimestamp(new \DateTime(date('Y-m-d H:i:s.'.$micro, $t)));
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
