<?php

namespace Smartbox\Integration\FrameworkBundle\Producers;

use BeSimple\SoapClient\SoapClient;
use Smartbox\Integration\FrameworkBundle\Endpoints\ConfigurableWebserviceEndpoint;
use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableSoapException;
use ProxyManager\Proxy\LazyLoadingInterface;
use Smartbox\Integration\FrameworkBundle\Util\SmokeTest\CanCheckConnectivityInterface;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AbstractSoapConfigurableProducer
 *
 * @package Smartbox\Integration\FrameworkBundle\Producers
 */
abstract class AbstractSoapConfigurableProducer extends ConfigurableProducer implements CanCheckConnectivityInterface
{
    const REQUEST_PARAMETERS = 'parameters';
    const REQUEST_NAME = 'name';
    const SOAP_METHOD_NAME = 'soap_method';
    const SOAP_OPTIONS = 'soap_options';
    const SOAP_HEADERS = 'soap_headers';

    /** @var  SoapClient */
    protected $soapClient;

    /**
     * @param $endpointOptions
     * @return SoapClient
     */
    public abstract function getSoapClient(array &$endpointOptions);

    /**
     * {@inheritDoc}
     */
    public function executeStep($stepAction, &$stepActionParams, &$endpointOptions, array &$context)
    {
        if(!parent::executeStep($stepAction,$stepActionParams,$endpointOptions,$context)){
            switch ($stepAction){
                case self::STEP_REQUEST:
                    $this->request($stepActionParams, $endpointOptions, $context);
                    return true;
            }
        }

        return false;
    }

    /**
     * @param string $methodName
     * @param array  $params
     * @param array  $endpointOptions
     * @param array  $soapOptions
     * @param array  $soapHeaders
     *
     * @return \stdClass
     */
    protected function performRequest($methodName, $params, array &$endpointOptions, array $soapOptions = [], array $soapHeaders = []){
        $soapClient = $this->getSoapClient($endpointOptions);
        try{
            if(!$soapClient){
                throw new \RuntimeException("SoapConfigurableProducer requires a SoapClient as a dependency");
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

            $soapClient->setExecutionTimeout($endpointOptions[ConfigurableWebserviceEndpoint::OPTION_TIMEOUT]);
            return $soapClient->__soapCall($methodName, $params, $soapOptions, $processedSoapHeaders);

        }catch (\Exception $ex){
            $this->throwSoapProducerException($soapClient, $ex->getMessage(), $ex->getCode(),$ex);
        }
    }

    /**
     * @param array $stepActionParams
     * @param array $endpointOptions
     * @param array $context
     * @return \stdClass
     * @throws RecoverableSoapException
     */
    protected function request(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $paramsResolver = new OptionsResolver();
        $paramsResolver->setRequired([
            self::SOAP_METHOD_NAME,
            self::REQUEST_PARAMETERS,
            self::REQUEST_NAME
        ]);

        $paramsResolver->setDefined([
            self::SOAP_OPTIONS,
            self::SOAP_HEADERS,
        ]);

        $params = $paramsResolver->resolve($stepActionParams);

        $requestName = $params[self::REQUEST_NAME];
        $soapMethodName = $params[self::SOAP_METHOD_NAME];
        $soapMethodParams = $this->resolve($params[self::REQUEST_PARAMETERS], $context);
        $soapOptions = isset($params[self::SOAP_OPTIONS]) ? $params[self::SOAP_OPTIONS] : [];
        $soapHeaders = isset($params[self::SOAP_HEADERS]) ? $params[self::SOAP_HEADERS] : [];

        $soapOptions['connection_timeout'] = $endpointOptions[ConfigurableWebserviceEndpoint::OPTION_CONNECT_TIMEOUT];

        $result = $this->performRequest($soapMethodName,$soapMethodParams,$endpointOptions, $soapOptions, $soapHeaders);

        $context[self::KEY_RESPONSES][$requestName] = $result;

        return $result;
    }


    protected function throwSoapProducerException(\SoapClient $soapClient, $message, $code = 0, $previousException = null)
    {
        /** @var \SoapClient $soapClient */
        $exception = new RecoverableSoapException(
            $message,
            $soapClient->__getLastRequestHeaders(),
            $soapClient->__getLastRequest(),
            $soapClient->__getLastResponseHeaders(),
            $soapClient->__getLastResponse(),
            $code,
            $previousException
        );

        throw $exception;
    }
    
    /**
     * {@inheritdoc}
     */
    public function checkConnectivityForSmokeTest(array $config = null)
    {
        $output = new SmokeTestOutput();

        try {
            $client = $this->getSoapClient($config);
            if ($client instanceof LazyLoadingInterface) {
                $client->initializeProxy();
            }
            $output->setCode($output::OUTPUT_CODE_SUCCESS);
            $output->addMessage('Connection was successfully established.');
        } catch (\SoapFault $e) {
            $output->setCode($output::OUTPUT_CODE_FAILURE);
            $output->addMessage(
                sprintf(
                    'Could not establish connection. Error: %s',
                    $e->getMessage()
                )
            );
        }

        return $output;
    }
}
