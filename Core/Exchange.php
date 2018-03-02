<?php

namespace Smartbox\Integration\FrameworkBundle\Core;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Traits\HasHeaders;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Traits\HasItinerary;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Exchange.
 */
class Exchange implements SerializableInterface
{
    const HEADER_PARENT_EXCHANGE = 'parent_exchange';
    const HEADER_HANDLER = 'handler';
    const HEADER_FROM = 'from';

    use HasInternalType;
    use HasItinerary;
    use HasHeaders;

    /**
     * @var int
     *
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     */
    protected $id;

    /**
     * @Assert\NotNull
     * @Assert\Valid
     *
     * @var MessageInterface
     *
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Core\Messages\Message")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     */
    protected $in;

    /**
     * @var MessageInterface
     *
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Core\Messages\Message")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     */
    protected $out;

    /**
     * Exchange constructor.
     *
     * @param MessageInterface|null $message
     * @param Itinerary|null        $itinerary
     */
    public function __construct(MessageInterface $message = null, Itinerary $itinerary = null, array $headers = [])
    {
        $this->id = uniqid('', true);
        $this->setIn($message);
        $this->itinerary = $itinerary;
        $this->headers = $headers;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return MessageInterface
     */
    public function getOut()
    {
        if (!$this->out && $this->getIn()) {
            $this->out = new Message(null, [], $this->getIn()->getContext());
        }

        return $this->out;
    }

    /**
     * @return bool
     */
    public function hasOut()
    {
        return !empty($this->out);
    }

    /**
     * @param MessageInterface $out
     */
    public function setOut(MessageInterface $out = null)
    {
        $this->out = $out;
    }

    /**
     * @return MessageInterface
     */
    public function getIn()
    {
        return $this->in;
    }

    /**
     * @param MessageInterface $in
     */
    public function setIn(MessageInterface $in = null)
    {
        $this->in = $in;
    }

    /**
     * @return MessageInterface
     */
    public function getResult()
    {
        return $this->hasOut() ? $this->getOut() : $this->getIn();
    }

    /**
     * Defines the clone behaviour for objects of this class.
     */
    public function __clone()
    {
        if ($this->in) {
            $this->in = unserialize(serialize($this->in));
        }

        if ($this->out) {
            $this->out = unserialize(serialize($this->out));
        }

        if ($this->itinerary) {
            $this->itinerary = clone $this->itinerary;
        }
    }
}
