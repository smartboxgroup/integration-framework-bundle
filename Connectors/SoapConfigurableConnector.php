<?php

namespace Smartbox\Integration\FrameworkBundle\Connectors;

use BeSimple\SoapClient\SoapClient;

class SoapConfigurableConnector extends AbstractSoapConfigurableConnector {
    /** @var  SoapClient */
    protected $soapClient;

    public function getSoapClient(array &$connectorOptions){
        return $this->soapClient;
    }

    public function setSoapClient(SoapClient $client){
        $this->soapClient = $client;
    }
}