<?php

namespace Smartbox\Integration\FrameworkBundle\Messages;


use Symfony\Component\Validator\Constraints as Assert;

class RetryExchangeEnvelope extends ExchangeEnvelope
{
    const KEY_RETRIES = 'retries';
    const HEADER_LAST_ERROR = 'last_error';

    public function __construct(Exchange $exchange = null, $retries = 0)
    {
        parent::__construct($exchange);
        $this->setRetries($retries);
    }
    /**
     * @Assert\Type(type="int")
     * @return int
     */
    public function getRetries()
    {
        return $this->getHeader(self::KEY_RETRIES);
    }

    /**
     * @param int $retries
     */
    public function setRetries($retries)
    {
        if (!is_int($retries)) {
            throw new \InvalidArgumentException("Expected integer as input");
        }
        $this->setHeader(self::KEY_RETRIES, $retries);
    }
}