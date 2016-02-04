<?php

namespace Smartbox\Integration\FrameworkBundle\Traits;


trait UsesSoapClient {

    /** @var \SoapClient */
    protected $soapClient;

    /**
     * @return \SoapClient
     */
    public function getSoapClient()
    {
        return $this->soapClient;
    }

    /**
     * @param \SoapClient $soapClient
     */
    public function setSoapClient($soapClient)
    {
        $this->soapClient = $soapClient;
    }
}