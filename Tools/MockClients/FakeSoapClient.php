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
    public function __construct($wsdl, array $options = array())
    {
        if (isset($options['MockCacheDir'])) {
            $this->cacheDir = $options['MockCacheDir'];
        }
        if ('true' === getenv('RECORD_RESPONSE')) {
            $this->saveWsdlToCache($wsdl, $options);
        }
        if ('true' === getenv('MOCKS_ENABLED')) {
            $wsdl = $this->getWsdlPathFromCache($wsdl, $options);
            $options['resolve_wsdl_remote_includes'] = false;
        }

        return parent::__construct($wsdl, $options);
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

        $mocksEnabled = getenv('MOCKS_ENABLED');
        $displayRequest = getenv('DISPLAY_REQUEST');
        $recordResponse = getenv('RECORD_RESPONSE');
        $prettyRecordedResponse = getenv('PRETTY_RECORDED_RESPONSE');

        if ('true' === $mocksEnabled) {
            $mocksMessage = 'MOCKS/';
        } else {
            $mocksMessage = '';
        }

        if ('true' === $displayRequest) {
            echo "\nREQUEST (".$mocksMessage."SOAP) for $location / $action / Version $version";
            echo "\n=====================================================================================================";
            echo "\nRAW REQUEST";
            echo "\n=====================================================================================================";
            echo "\n".$request;
            echo "\n=====================================================================================================";
            echo "\nPRETTY SEXY REQUEST";
            echo "\n=====================================================================================================";
            echo "\n".$this->prettyXML($request);
            echo "\n=====================================================================================================";
            echo "\n\n";
        }

        if ('true' === $mocksEnabled) {
            try {
                $response = $this->getResponseFromCache($actionName, self::CACHE_SUFFIX);
                $this->lastResponseCode = 200;
            } catch (\InvalidArgumentException $e) {
                throw $e;
            }
        } else {
            $response = parent::__doRequest($request, $location, $action, $version, $oneWay);
        }

        if ('true' === $displayRequest) {
            echo "\nRESPONSE (".$mocksMessage."SOAP) for $location / $action / Version $version";
            echo "\n=====================================================================================================";
            echo "\nRAW RESPONSE";
            echo "\n=====================================================================================================";
            echo "\n".$response;
            echo "\n=====================================================================================================";
            echo "\nPRETTY SEXY RESPONSE";
            echo "\n=====================================================================================================";
            echo "\n".$this->prettyXML($response);
            echo "\n=====================================================================================================";
            echo "\n\n";
        }

        if ('true' === $recordResponse) {
            $recordedResponse = $response;
            if ('true' === $prettyRecordedResponse) {
                $recordedResponse = $this->prettyXML($response);
            }
            $this->setResponseInCache($actionName, $recordedResponse, self::CACHE_SUFFIX);
        }

        return $response;
    }

    /** Return a much nicer XML string.
     *
     * @param $uglyXML
     *
     * @return string
     */
    private function prettyXML($uglyXML)
    {
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($uglyXML);

        return $doc->saveXML();
    }
}
