<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;


use Smartbox\Integration\FrameworkBundle\Core\Exchange;

trait ExchangeAwareTrait {

    /** @var  \Smartbox\Integration\FrameworkBundle\Core\Exchange */
    protected $exchange;

    /**
     * @param \Smartbox\Integration\FrameworkBundle\Core\Exchange $exchange
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