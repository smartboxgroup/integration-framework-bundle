<?php

namespace Smartbox\Integration\FrameworkBundle\Messages;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ExchangeEnvelope
 * @package Smartbox\Integration\FrameworkBundle\Messages
 */
class ExchangeEnvelope extends Message
{
    /**
     * @Assert\Valid
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Messages\Exchange")
     * @JMS\Expose
     * @JMS\Groups({"body"})
     * @var Exchange
     */
    protected $body;

    public function __construct(Exchange $exchange = null)
    {
        parent::__construct($exchange, array(), $exchange->getIn()->getContext());
    }

    /**
     * @return Exchange
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return Exchange
     */
    public function getExchange(){
        return $this->body;
    }

    /**
     * @param Exchange $exchange
     */
    public function setBody(SerializableInterface $exchange = null)
    {
        if (!$exchange instanceof Exchange) {
            throw new \InvalidArgumentException("The ExchangeEnvelope is a specialized message that requires an Exchange as body");
        }

        $this->body = $exchange;
    }
}
