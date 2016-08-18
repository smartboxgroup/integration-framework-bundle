<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Soap;

use BeSimple\SoapClient\SoapClient;

class SoapConfigurableProducer extends AbstractSoapConfigurableProducer
{
    /** @var SoapClient */
    protected $soapClient;

    /**
     * {@inheritdoc}
     */
    public function getSoapClient(array &$options)
    {
        return $this->soapClient;
    }

    /**
     * {@inheritdoc}
     */
    public function setSoapClient(SoapClient $client)
    {
        $this->soapClient = $client;
    }
}
