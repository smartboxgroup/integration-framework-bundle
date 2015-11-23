<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions;


use Smartbox\Integration\FrameworkBundle\Messages\Exchange;

interface ExchangeAwareInterface {

    /**
     * @param Exchange $exchange
     * @return mixed
     */
    public function setExchange(Exchange $exchange);

    /**
     * @return Exchange
     */
    public function getExchange();
}