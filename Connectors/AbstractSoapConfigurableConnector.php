<?php

namespace Smartbox\Integration\FrameworkBundle\Connectors;

use Smartbox\Integration\FrameworkBundle\Exceptions\SoapConnectorException;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractSoapConfigurableConnector extends ConfigurableConnector {
    const REQUEST_PARAMETERS = 'parameters';
    const REQUEST_NAME = 'name';
    const SOAP_METHOD_NAME = 'soap_method';

    /** @var  \SoapClient */
    protected $soapClient;

    public abstract function getSoapClient($connectorOptions);

    /**
     * {@inheritDoc}
     */
    public function executeStep($stepAction, $stepActionParams, $options, array &$context)
    {
        if(!parent::executeStep($stepAction,$stepActionParams,$options,$context)){
            switch ($stepAction){
                case self::STEP_REQUEST:
                    $this->request($stepActionParams, $options, $context);
                    return true;
            }
        }

        return false;
    }

    protected function performRequest($methodName,$params,$connectorOptions){
        $soapClient = $this->getSoapClient($connectorOptions);
        if(!$soapClient){
            throw new \RuntimeException("SoapConfigurableConnector requires a SoapClient as a dependency");
        }

        return $soapClient->__call($methodName,$params);
    }

    protected function request(array $stepActionParams, array $connectorOptions, array &$context)
    {
        $paramsResolver = new OptionsResolver();
        $paramsResolver->setRequired([
            self::SOAP_METHOD_NAME,
            self::REQUEST_PARAMETERS,
            self::REQUEST_NAME
        ]);

        $params = $paramsResolver->resolve($stepActionParams);

        $requestName = $params[self::REQUEST_NAME];
        $soapMethodName = $params[self::SOAP_METHOD_NAME];
        $soapMethodParams = $this->resolve($params[self::REQUEST_PARAMETERS], $context);

        $soapClient = $this->getSoapClient($connectorOptions);

        try{
            $result = $this->performRequest($soapMethodName,$soapMethodParams,$connectorOptions);
        }catch (\Exception $ex){
            $exception = new SoapConnectorException($ex->getMessage());
            $exception->setRawRequest($soapClient->__getLastRequest());
            $exception->setRawResponse($soapClient->__getLastResponse());
            throw $exception;
        }

        $context[self::KEY_RESPONSES][$requestName] = $result;
    }
}