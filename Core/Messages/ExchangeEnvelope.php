<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Messages;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ExchangeEnvelope.
 */
class ExchangeEnvelope extends Message
{
    /**
     * @Assert\Valid
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Core\Exchange")
     * @JMS\Expose
     * @JMS\Groups({"body"})
     *
     * @var Exchange
     */
    protected $body;

    /**
     * {@inheritdoc}
     */
    public function __construct(Exchange $exchange)
    {
        parent::__construct($exchange, [], $exchange->getIn()->getContext());
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
    public function getExchange()
    {
        return $this->body;
    }

    /**
     * @param SerializableInterface|Exchange $exchange
     */
    public function setBody(SerializableInterface $exchange = null)
    {
        if (!$exchange instanceof Exchange) {
            throw new \InvalidArgumentException(
                'The ExchangeEnvelope is a specialized message that requires an Exchange as body'
            );
        }

        $this->body = $exchange;
    }
}
