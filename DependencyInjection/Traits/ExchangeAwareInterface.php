<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;

/**
 * Interface ExchangeAwareInterface.
 */
interface ExchangeAwareInterface
{
    /**
     * @param Exchange $exchange
     *
     * @return mixed
     */
    public function setExchange(Exchange $exchange);

    /**
     * @return Exchange
     */
    public function getExchange();
}
