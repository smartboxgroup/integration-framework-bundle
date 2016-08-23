<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;

/**
 * Trait ExchangeAwareTrait.
 */
trait ExchangeAwareTrait
{
    /** @var Exchange */
    protected $exchange;

    /**
     * @param Exchange $exchange
     */
    public function setExchange(Exchange $exchange)
    {
        $this->exchange = $exchange;
    }

    /**
     * @return Exchange
     */
    public function getExchange()
    {
        return $this->exchange;
    }
}
