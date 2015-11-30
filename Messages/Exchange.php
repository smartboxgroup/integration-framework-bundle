<?php

namespace Smartbox\Integration\FrameworkBundle\Messages;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasType;
use Smartbox\Integration\FrameworkBundle\Messages\Traits\HasHeaders;
use Smartbox\Integration\FrameworkBundle\Messages\Traits\HasItinerary;
use Smartbox\Integration\FrameworkBundle\Processors\Itinerary;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Exchange
 * @package Smartbox\Integration\FrameworkBundle\Messages
 */
class Exchange implements SerializableInterface
{
    const HEADER_PARENT_EXCHANGE = 'parent_exchange';
    const HEADER_HANDLER = 'handler';
    const HEADER_FROM = 'from';

    use HasType;
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
     * @var MessageInterface
     *
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Messages\Message")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     */
    protected $in;

    /**
     * @var MessageInterface
     *
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Messages\Message")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     */
    protected $out;


    public function __construct(MessageInterface $message = null, Itinerary $itinerary = null)
    {
        $this->setIn($message);
        $this->id = uniqid();
        $this->itinerary = $itinerary;
    }


    public function getId()
    {
        return $this->id;
    }

    /**
     * @return MessageInterface
     */
    public function getOut()
    {
        if (!$this->out) {
            $this->out = new Message();
        }

        return $this->out;
    }

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

    public function __clone(){
        if($this->in){
            $this->in = unserialize(serialize($this->in));
        }

        if($this->out){
            $this->out = unserialize(serialize($this->out));
        }

        if($this->itinerary){
            $this->itinerary = clone $this->itinerary;
        }
    }
}
