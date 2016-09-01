<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\MockClients;

use BeSimple\SoapClient\BasicAuthSoapClient;
use BeSimple\SoapCommon\Cache;

class FakeSoapClient extends BasicAuthSoapClient
{
    use FakeClientTrait;

    const CACHE_SUFFIX = 'xml';

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
        // The resource name is generated in the same way as in BeSimple\SoapClient\WsdlDownloader
        $resourceName = 'wsdl_'.md5($wsdl).'.cache';

        if (getenv('MOCKS_ENABLED') === 'true') {
            try {
                $fileName = $this->getFileName($resourceName);

                return $this->fileLocator->locate($fileName);
            } catch (\InvalidArgumentException $e) {
                throw $e;
            }
        }

        if (getenv('RECORD_RESPONSE') === 'true') {
            Cache::setDirectory($this->getCacheDir());
        }

        return parent::loadWsdl($wsdl, $options);
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
