<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Soap;

use BeSimple\SoapClient\SoapClient;

class SoapConfigurableProducer extends AbstractSoapConfigurableProducer
{
    /** @var  SoapClient */
    protected $soapClient;

    /**
     * {@inheritDoc}
     */
    public function getSoapClient(array &$options)
    {
        return $this->soapClient;
    }

    /**
     * {@inheritDoc}
     */
    public function setSoapClient(SoapClient $client)
    {
        $this->soapClient = $client;
    }
}
