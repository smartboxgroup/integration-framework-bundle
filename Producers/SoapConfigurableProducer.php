<?php

namespace Smartbox\Integration\FrameworkBundle\Producers;

use BeSimple\SoapClient\SoapClient;

class SoapConfigurableProducer extends AbstractSoapConfigurableProducer {
    /** @var  SoapClient */
    protected $soapClient;

    public function getSoapClient(array &$producerOptions){
        return $this->soapClient;
    }

    public function setSoapClient(SoapClient $client){
        $this->soapClient = $client;
    }
}