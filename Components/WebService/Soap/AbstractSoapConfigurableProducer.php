<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Soap;

use BeSimple\SoapClient\SoapClient;
use ProxyManager\Proxy\LazyLoadingInterface;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;
use Smartbox\Integration\FrameworkBundle\Components\WebService\AbstractWebServiceProducer;
use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Soap\Exceptions\RecoverableSoapException;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Soap\Exceptions\UnrecoverableSoapException;
use Smartbox\Integration\FrameworkBundle\Tools\SmokeTests\CanCheckConnectivityInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Smartbox\Integration\FrameworkBundle\Events\ExternalSystemHTTPEvent;

/**
 * Class AbstractSoapConfigurableProducer.
 */
abstract class AbstractSoapConfigurableProducer extends AbstractWebServiceProducer implements CanCheckConnectivityInterface
{
    const REQUEST_PARAMETERS = 'parameters';
    const REQUEST_NAME = 'name';
    const SOAP_METHOD_NAME = 'soap_method';
    const SOAP_OPTIONS = 'soap_options';
    const SOAP_HEADERS = 'soap_headers';
    const VALIDATION = 'validations';
    const VALIDATION_RULE = 'rule';
    const VALIDATION_MESSAGE = 'message';
    const VALIDATION_DISPLAY_MESSAGE = 'display_message';
    const VALIDATION_RECOVERABLE = 'recoverable';

    /** @var SoapClient */
    protected $soapClient;

    /**
     * @param $endpointOptions
     *
     * @return \BeSimple\SoapClient\SoapClient
     */
    abstract public function getSoapClient(array &$endpointOptions);

    /**
     * {@inheritdoc}
     */
    public function executeStep($stepAction, &$stepActionParams, &$endpointOptions, array &$context)
    {
        if (!parent::executeStep($stepAction, $stepActionParams, $endpointOptions, $context)) {
            switch ($stepAction) {
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
    protected function performRequest($methodName, $params, array &$endpointOptions, array $soapOptions = [], array $soapHeaders = [])
    {
        $soapClient = $this->getSoapClient($endpointOptions);
        $response = null;
        try {
            if (!$soapClient) {
                throw new \RuntimeException('SoapConfigurableProducer requires a SoapClient as a dependency');
            }

            // creates a proper set of SoapHeader objects
            $processedSoapHeaders = array_map(function ($header) {
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

            $soapClient->setExecutionTimeout($endpointOptions[ConfigurableWebserviceProtocol::OPTION_TIMEOUT]);

            $response = $soapClient->__soapCall($methodName, $params, $soapOptions, $processedSoapHeaders);
            error_log("MEL_WAS_HERE:001\n");
            $this->getEventDispatcher()->dispatch(ExternalSystemHTTPEvent::EVENT_NAME, $this->getExternalSystemHTTPEvent($response));
        } catch (\Exception $ex) {
            $this->throwRecoverableSoapProducerException($ex->getMessage(), $soapClient, false, $ex->getCode(), $ex);
        }

        return $response;
    }

    /**
     * @param array $stepActionParams
     * @param array $endpointOptions
     * @param array $context
     *
     * @return \stdClass
     *
     * @throws RecoverableSoapException
     * @throws UnrecoverableSoapException
     */
    protected function request(array &$stepActionParams, array &$endpointOptions, array &$context)
    {
        $paramsResolver = new OptionsResolver();
        $paramsResolver->setRequired([
            self::SOAP_METHOD_NAME,
            self::REQUEST_PARAMETERS,
            self::REQUEST_NAME,
        ]);

        $paramsResolver->setDefined([
            self::SOAP_OPTIONS,
            self::SOAP_HEADERS,
            self::VALIDATION,
        ]);

        $params = $paramsResolver->resolve($stepActionParams);

        // parses validation steps (if any)
        $validationSteps = [];
        if (isset($params[self::VALIDATION]) && !empty($params[self::VALIDATION])) {
            if (!is_array($params[self::VALIDATION])) {
                $params[self::VALIDATION] = [$params[self::VALIDATION]];
            }
            $validationParamsResolver = new OptionsResolver();
            $validationParamsResolver->setRequired([
                self::VALIDATION_RULE,
                self::VALIDATION_MESSAGE,
                self::VALIDATION_RECOVERABLE,
            ]);

            $validationParamsResolver->setDefined([
                self::VALIDATION_DISPLAY_MESSAGE,
            ]);

            foreach ($params[self::VALIDATION] as $validation) {
                $validationSteps[] = $validationParamsResolver->resolve($validation);
            }
        }

        $requestName = $params[self::REQUEST_NAME];
        $soapMethodName = $this->confHelper->resolve($params[self::SOAP_METHOD_NAME], $context);
        $soapMethodParams = $this->confHelper->resolve($params[self::REQUEST_PARAMETERS], $context);
        $soapOptions = isset($params[self::SOAP_OPTIONS]) ? $params[self::SOAP_OPTIONS] : [];
        $soapHeaders = isset($params[self::SOAP_HEADERS]) ? $params[self::SOAP_HEADERS] : [];

        $soapOptions['connection_timeout'] = $endpointOptions[ConfigurableWebserviceProtocol::OPTION_CONNECT_TIMEOUT];

        $result = $this->performRequest($soapMethodName, $soapMethodParams, $endpointOptions, $soapOptions, $soapHeaders);
        $context[self::KEY_RESPONSES][$requestName] = $result;

        // Validates response (if needed)
        foreach ($validationSteps as $validationStep) {
            $isValid = $this->confHelper->evaluateStringOrExpression($validationStep[self::VALIDATION_RULE], $context);
            if (!$isValid) {
                $message = $this->confHelper->evaluateStringOrExpression($validationStep[self::VALIDATION_MESSAGE], $context);
                $recoverable = $validationStep[self::VALIDATION_RECOVERABLE];

                $soapClient = $this->getSoapClient($endpointOptions);
                $showMessage = (
                    isset($validationStep[self::VALIDATION_DISPLAY_MESSAGE]) &&
                    true === $validationStep[self::VALIDATION_DISPLAY_MESSAGE]
                );
                if ($recoverable) {
                    $this->throwRecoverableSoapProducerException($message, $soapClient, $showMessage);
                } else {
                    $this->throwUnrecoverableSoapProducerException($message, $soapClient, $showMessage);
                }
            }
        }

        return $result;
    }

    /**
     * @param string      $message
     * @param \SoapClient $soapClient
     * @param bool        $showMessage
     * @param int         $code
     * @param null        $previousException
     *
     * @throws \Smartbox\Integration\FrameworkBundle\Components\WebService\Soap\Exceptions\RecoverableSoapException
     */
    protected function throwRecoverableSoapProducerException($message, \SoapClient $soapClient, $showMessage = false, $code = 0, $previousException = null)
    {
        /* @var \SoapClient $soapClient */
        $exception = new RecoverableSoapException(
            $message,
            $soapClient->__getLastRequestHeaders(),
            $soapClient->__getLastRequest(),
            $soapClient->__getLastResponseHeaders(),
            $soapClient->__getLastResponse(),
            $code,
            $previousException
        );
        $exception->setShowExternalSystemErrorMessage($showMessage);
        $exception->setExternalSystemName($this->getName());

        throw $exception;
    }

    /**
     * @param string      $message
     * @param \SoapClient $soapClient
     * @param bool        $showMessage
     * @param int         $code
     * @param null        $previousException
     *
     * @throws \Smartbox\Integration\FrameworkBundle\Components\WebService\Soap\Exceptions\UnrecoverableSoapException
     */
    protected function throwUnrecoverableSoapProducerException($message, \SoapClient $soapClient, $showMessage = false, $code = 0, $previousException = null)
    {
        /* @var \SoapClient $soapClient */
        $exception = new UnrecoverableSoapException(
            $message,
            $soapClient->__getLastRequestHeaders(),
            $soapClient->__getLastRequest(),
            $soapClient->__getLastResponseHeaders(),
            $soapClient->__getLastResponse(),
            $code,
            $previousException
        );
        $exception->setShowExternalSystemErrorMessage($showMessage);
        $exception->setExternalSystemName($this->getName());

        throw $exception;
    }

    /**
     * {@inheritdoc}
     */
    public function checkConnectivityForSmokeTest(array $config = [])
    {
        $output = new SmokeTestOutput();

        try {
            $client = $this->getSoapClient($config);
            if ($client instanceof LazyLoadingInterface) {
                $client->initializeProxy();
            }
            $output->setCode($output::OUTPUT_CODE_SUCCESS);
            $output->addSuccessMessage('Connection was successfully established.');
        } catch (\SoapFault $e) {
            $output->setCode($output::OUTPUT_CODE_FAILURE);
            $output->addFailureMessage(
                sprintf(
                    'Could not establish connection. Error: %s',
                    $e->getMessage()
                )
            );
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public static function getConnectivitySmokeTestLabels()
    {
        return '';
    }

    public function getExternalSystemHTTPEvent($soapClientResponse)
    {
        // Dispatch event with error information
        $event = new ExternalSystemHTTPEvent();
        $event->setStatus('melbo was here.soap.');
        //$event->setTimestampToCurrent();
        return $event;
    }

}
