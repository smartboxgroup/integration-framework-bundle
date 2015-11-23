<?php

namespace Smartbox\Integration\FrameworkBundle\Events;

use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class NewExchangeEvent
 * @package Smartbox\Integration\FrameworkBundle\Events
 */
class NewExchangeEvent extends Event {

    const TYPE_NEW_EXCHANGE_EVENT = "smartesb.exchange.new";

    /**
     * @param Exchange $exchange
     */
    public function __construct(Exchange $exchange){
        parent::__construct(self::TYPE_NEW_EXCHANGE_EVENT);
        $this->exchange = $exchange;
    }

    /**
     * @Assert\Valid
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Messages\Exchange")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @var Exchange
     */
    protected $exchange;

    /**
     * @return Exchange
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @param Exchange $exchange
     */
    public function setExchange(Exchange $exchange)
    {
        $this->exchange = $exchange;
    }

}