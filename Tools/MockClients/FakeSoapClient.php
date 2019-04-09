<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\MockClients;

use BeSimple\SoapClient\SoapClient;
use BeSimple\SoapClient\Curl;
use BeSimple\SoapClient\SoapRequest;

class FakeSoapClient extends SoapClient
{
    use FakeClientTrait;

    const CACHE_SUFFIX = 'xml';

    /**
     * {@inheritdoc}
     */
    public function __construct($wsdl, array $options = array())
    {
        if (isset($options['MockCacheDir'])) {
            $this->cacheDir = $options['MockCacheDir'];
        }
        if (getenv('RECORD_RESPONSE') === 'true') {
            $this->saveWsdlToCache($wsdl, $options);
        }
        if (getenv('MOCKS_ENABLED') === 'true') {
            $wsdl = $this->getWsdlPathFromCache($wsdl, $options);
            $options['resolve_wsdl_remote_includes'] = false;
        }

        return parent::__construct($wsdl, $options);
    }

    /**
     * {@inheritDoc}
     */
    protected function filterRequestHeaders(SoapRequest $soapRequest, array $headers)
    {
        if (isset($this->_login) && isset($this->_password) && $this->authType !== Curl::AUTH_TYPE_NONE) {
            if($this->authType === Curl::AUTH_TYPE_BASIC) {
                $authToken = base64_encode(sprintf('%s:%s', $this->_login, $this->_password));
                $headers[] = sprintf('Authorization: Basic %s', $authToken);
            }
        }

        return parent::filterRequestHeaders($soapRequest, $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function __call($functionName, $arguments)
    {
        $this->checkInitialisation();
        $this->actionName = $functionName;

        return parent::__soapCall($functionName, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function __soapCall($functionName, $arguments, $options = null, $inputHeaders = null, &$outputHeaders = null)
    {
        $this->checkInitialisation();
        $this->actionName = $functionName;

        return parent::__soapCall($functionName, $arguments, $options, $inputHeaders, $outputHeaders);
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
                $response = $this->getResponseFromCache($actionName, self::CACHE_SUFFIX);
                $this->lastResponseCode = 200;
                return $response;
            } catch (\InvalidArgumentException $e) {
                throw $e;
            }
        }

        $response = parent::__doRequest($request, $location, $action, $version, $oneWay);

        if (getenv('DISPLAY_REQUEST') === 'true') {
            echo "\nREQUEST for $location / $action / Version $version";
            echo "\n=====================================================================================================";
            echo "\n".$request;
            echo "\n=====================================================================================================";
            echo "\n\n";
            echo "\nRESPONSE";
            echo "\n=====================================================================================================";
            echo "\n".$response;
            echo "\n=====================================================================================================";
            echo "\n=====================================================================================================";
            echo "\n\n";
        }

        if (getenv('RECORD_RESPONSE') === 'true') {
            $this->setResponseInCache($actionName, $response, self::CACHE_SUFFIX);
        }

        return $response;
    }

}
