<?php

namespace Smartbox\Integration\FrameworkBundle\Connectors;


use Smartbox\Integration\FrameworkBundle\Exceptions\SoapConnectorException;
use Smartbox\Integration\FrameworkBundle\Traits\UsesSoapClient;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SoapConfigurableConnector extends ConfigurableConnector {
    use UsesSoapClient;

    const REQUEST_PARAMETERS = 'parameters';
    const REQUEST_NAME = 'name';
    const SOAP_METHOD_NAME = 'soap_method';
    const SOAP_OPTIONS = 'soap_options';
    const SOAP_HEADERS = 'soap_headers';

    protected function request(array $stepActionParams, array $connectorOptions, array &$context)
    {
        $paramsResolver = new OptionsResolver();
        $paramsResolver->setRequired([
            self::SOAP_METHOD_NAME,
            self::REQUEST_PARAMETERS,
            self::REQUEST_NAME
        ]);

        $paramsResolver->setDefined([
            self::SOAP_OPTIONS,
            self::SOAP_HEADERS
        ]);

        $params = $paramsResolver->resolve($stepActionParams);

        $requestName = $params[self::REQUEST_NAME];
        $soapMethodName = $params[self::SOAP_METHOD_NAME];
        $soapMethodParams = $this->resolve($params[self::REQUEST_PARAMETERS], $context);
        $soapOptions = isset($params[self::SOAP_OPTIONS]) ? $params[self::SOAP_OPTIONS] : [];
        $soapHeaders = isset($params[self::SOAP_HEADERS]) ? $params[self::SOAP_HEADERS] : [];

        $soapClient = $this->getSoapClient();
        if(!$soapClient){
            throw new \RuntimeException("SoapConfigurableConnector requires a SoapClient as a dependency");
        }

        // creates a proper set of SoapHeader objects
        $processedSoapHeaders = array_map(function($header){
            if (is_array($header)) {
                $header = new \SoapHeader($header[0], $header[1], $header[2]);
            }

            if (!$header instanceof \SoapHeader) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid soap header "%s". Expected instance of \SoapHeader or array containing 3 values representing'.
                    ' "namespace", "header name" and "header value"',
                    json_encode($header)
                ));
            }

            return $header;
        }, $soapHeaders);

        try{
            $result = $soapClient->__soapCall($soapMethodName, $soapMethodParams, $soapOptions, $processedSoapHeaders);
        }catch (\Exception $ex){
            $exception = new SoapConnectorException($ex->getMessage());
            $exception->setRawRequest($soapClient->__getLastRequest());
            $exception->setRawResponse($soapClient->__getLastResponse());
            throw $exception;
        }

        $context[self::KEY_RESPONSES][$requestName] = $result;
    }
}
