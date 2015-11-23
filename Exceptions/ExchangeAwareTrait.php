<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions;


use Smartbox\Integration\FrameworkBundle\Messages\Exchange;

trait ExchangeAwareTrait {

    /** @var  Exchange */
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
    public function getExchange(){
        return $this->exchange;
    }
}