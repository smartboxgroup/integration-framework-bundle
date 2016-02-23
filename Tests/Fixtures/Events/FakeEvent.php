<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Events;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class FakeEvent
 * @package Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Events
 */
class FakeEvent implements SerializableInterface
{
    use HasInternalType;

    public function __construct()
    {
    }

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("DateTime<'Y-m-d\TH:i:s.uP'>")
     * @var \DateTime
     */
    protected $timestamp;

    /**
     * @Assert\Type(type="string")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("string")
     * @var string
     */
    protected $name;


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
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
