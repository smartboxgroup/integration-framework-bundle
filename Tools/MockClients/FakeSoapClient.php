<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\MockClients;

use BeSimple\SoapClient\BasicAuthSoapClient;

class FakeSoapClient extends BasicAuthSoapClient
{
    use FakeClientTrait;

    const CACHE_SUFFIX = 'xml';

    const WSDL_SUFFIX = 'wsdl';

    /**
     * {@inheritdoc}
     */
    public function __construct($wsdl, array $options)
    {
        // Init method has to be executed in the constructor so that wsdl files can be cached.
        $this->init($options['file_locator'], $options['cache_dir'], $options['cache_exclusions']);
        unset($options['file_locator'], $options['cache_dir'], $options['cache_exclusions']);

        parent::__construct($wsdl, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadWsdl($wsdl, array $options)
    {
        $this->checkInitialisation();
        $resourceName = md5($wsdl).'_'.self::WSDL_SUFFIX;

        if (getenv('MOCKS_ENABLED') === 'true') {
            try {
                $fileName = $this->getFileName($resourceName, self::CACHE_SUFFIX);

                return $this->fileLocator->locate($fileName);
            } catch (\InvalidArgumentException $e) {
                throw $e;
            }
        }

        $cacheFileName = parent::loadWsdl($wsdl, $options);

        if (getenv('RECORD_RESPONSE') === 'true') {
            $wsdlFile = file_get_contents($cacheFileName);
            $this->setResponseInCache($resourceName, $wsdlFile, self::CACHE_SUFFIX);
        }

        return $cacheFileName;
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
