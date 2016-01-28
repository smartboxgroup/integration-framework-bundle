<?php

namespace Smartbox\Integration\FrameworkBundle\Connectors;


use BeSimple\SoapClient\SoapClient;
use Smartbox\Integration\FrameworkBundle\Exceptions\ConnectorUnrecoverableException;
use Smartbox\Integration\FrameworkBundle\Exceptions\SoapConnectorException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SoapConfigurableConnector extends ConfigurableConnector {
    const AUTH_BASIC = 'basic';
    const OPTION_AUTH = 'authentication';
    const OPTION_WSDL_URI = 'wsdl_uri';

    const REQUEST_PARAMETERS = 'parameters';
    const REQUEST_NAME = 'name';
    const SOAP_METHOD_NAME = 'soap_method';

    protected function request(array $stepActionParams, array $connectorOptions, array &$context)
    {
        $paramsResolver = new OptionsResolver();
        $paramsResolver->setRequired([
            self::SOAP_METHOD_NAME,
            self::REQUEST_PARAMETERS,
            self::REQUEST_NAME
        ]);

        $params = $paramsResolver->resolve($stepActionParams);

        $soapOptions = [
            'trace' => 1,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS
        ];

        if(array_key_exists(self::OPTION_AUTH,$connectorOptions) && $connectorOptions[self::OPTION_AUTH] == self::AUTH_BASIC){
            if(!array_key_exists(self::OPTION_USERNAME, $connectorOptions) || !array_key_exists(self::OPTION_PASSWORD,$connectorOptions)){
                throw new ConnectorUnrecoverableException("Missing username or password for basic auth");
            }

            $soapOptions['login'] = $connectorOptions[self::OPTION_USERNAME];
            $soapOptions['password'] = $connectorOptions[self::OPTION_PASSWORD];
        }

        $requestName = $params[self::REQUEST_NAME];
        $soapMethodName = $params[self::SOAP_METHOD_NAME];
        $soapMethodParams = $this->resolve($params[self::REQUEST_PARAMETERS], $context);

        $wsdlURI = $connectorOptions[self::OPTION_WSDL_URI];
        $soapClient = new \SoapClient($wsdlURI,$soapOptions);

        try{
            $result = $soapClient->__soapCall($soapMethodName,$soapMethodParams);
        }catch (\Exception $ex){
            $exception = new SoapConnectorException($ex->getMessage());
            $exception->setRawRequest($soapClient->__getLastRequest());
            $exception->setRawResponse($soapClient->__getLastResponse());
            throw $exception;
        }

        $context[self::KEY_RESPONSES][$requestName] = $result;
    }

    public function getAvailableOptions()
    {
        return array_merge(
            parent::getAvailableOptions(),
            [
                self::OPTION_AUTH => [
                    'Authentication method',
                    [
                        self::AUTH_BASIC => 'Use this method for basic http authentication'
                    ]
                ],
                self::OPTION_WSDL_URI => ['WSDL URI', []],
            ]
        );

    }
}