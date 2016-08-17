<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\MockClients;

use BeSimple\SoapClient\BasicAuthSoapClient;

class FakeSoapClient extends BasicAuthSoapClient
{
    use FakeClientTrait;

    const CACHE_SUFFIX = 'xml';

    /**
     * {@inheritdoc}
     */
    public function __call($function_name, $arguments)
    {
        $this->checkInitialisation();
        $this->actionName = $function_name;

        return parent::__soapCall($function_name, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function __soapCall($function_name, $arguments, $options = null, $input_headers = null, &$output_headers = null)
    {
        $this->checkInitialisation();
        $this->actionName = $function_name;

        return parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
    }

    /**
     * {@inheritdoc}
     */
    public function __doRequest($request, $location, $action, $version, $oneWay = 0)
    {
        $this->checkInitialisation();
        $actionName = md5($location).'_'.$this->actionName;

        if (getenv('MOCKS_ENABLED') === 'true') {
            try {
                return $this->getResponseFromCache($actionName, self::CACHE_SUFFIX);
            } catch (\InvalidArgumentException $e) {
                throw $e;
            }
        }

        $response = parent::__doRequest($request, $location, $action, $version, $oneWay);

        if (getenv('RECORD_RESPONSE') === 'true') {
            $this->setResponseInCache($actionName, $response, self::CACHE_SUFFIX);
        }

        return $response;
    }
}
