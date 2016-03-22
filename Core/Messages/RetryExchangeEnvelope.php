<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Messages;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class RetryExchangeEnvelope.
 */
class RetryExchangeEnvelope extends ErrorExchangeEnvelope
{
    const KEY_RETRIES = 'retries';
    const HEADER_LAST_ERROR = 'last_error';

    /**
     * RetryExchangeEnvelope constructor.
     *
     * @param Exchange|null          $exchange
     * @param SerializableArray|null $processingContext
     * @param int                    $retries
     */
    public function __construct(Exchange $exchange = null, SerializableArray $processingContext = null, $retries = 0)
    {
        parent::__construct($exchange, $processingContext);
        $this->setRetries($retries);
    }
    /**
     * @Assert\Type(type="int")
     *
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
            throw new \InvalidArgumentException('Expected integer as input');
        }
        $this->setHeader(self::KEY_RETRIES, $retries);
    }
}
