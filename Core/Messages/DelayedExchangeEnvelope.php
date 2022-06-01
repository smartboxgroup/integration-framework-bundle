<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Messages;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;

class DelayedExchangeEnvelope extends ExchangeEnvelope
{
    const HEADER_DELAY_PERIOD = 'delay_period';

    public function __construct(Exchange $exchange, int $delayPeriod)
    {
        parent::__construct($exchange);

        $this->setHeader(self::HEADER_DELAY_PERIOD, $delayPeriod);
    }

}
