<?php

namespace Smartbox\Integration\FrameworkBundle\Events;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Tools\Helper\DateTimeHelper;
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
     * @JMS\Expose
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $eventDetails = '';

    /**
     * Constructor.
     *
     * @param string|null $eventName
     */
    public function __construct($eventName = null)
    {
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
        $this->setTimestamp(DateTimeHelper::createDateTimeFromCurrentMicrotime());
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

    /**
     * @return string
     */
    public function getEventDetails()
    {
        return $this->eventDetails;
    }

    /**
     * @param string $eventDetails
     */
    public function setEventDetails($eventDetails)
    {
        $this->eventDetails = $eventDetails;
    }
}
