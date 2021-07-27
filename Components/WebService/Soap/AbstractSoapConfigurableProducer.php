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
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AbstractSoapConfigurableProducer.
 */
abstract class AbstractSoapConfigurableProducer extends AbstractWebServiceProducer implements CanCheckConnectivityInterface
{
    use ParseHeadersTrait;

    const REQUEST_PARAMETERS = 'parameters';
    const REQUEST_NAME = 'name';
    const SOAP_METHOD_NAME = 'soap_method';
    const SOAP_OPTIONS = 'soap_options';
    const SOAP_HEADERS = 'soap_headers';
    const HTTP_HEADERS = 'http_headers';
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
     * @param $methodName
     * @param $params
     * @param array $endpointOptions
     * @param array $soapOptions
     * @param array $soapHeaders
     * @param bool  $displayError
     *
     * @return mixed|null
     *
     * @throws RecoverableSoapException
     */
    protected function performRequest($methodName, $params, array &$endpointOptions, array $soapOptions = [], array $soapHeaders = [])
    {
        $soapClient = $this->getSoapClient($endpointOptions);
        $response = null;

        if (!$soapClient) {
            throw new \RuntimeException('SoapConfigurableProducer requires a SoapClient as a dependency');
        }

        try {
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

            $response = $soapClient->__soapCall($methodName, $params, $soapOptions, $processedSoapHeaders);

            $lastResponseCode = $soapClient->__getLastResponseCode();
            if ($lastResponseCode >= 400 && $lastResponseCode <= 599) {
                $this->throwUnrecoverableSoapProducerException('Unrecoverable error. SOAP HTTP Response code is '.$lastResponseCode.', 200 was expected.', $soapClient);
            } elseif (200 != $lastResponseCode) {
                $this->throwRecoverableSoapProducerException('Recoverable error. SOAP HTTP Response code is '.$lastResponseCode.', 200 was expected.', $soapClient);
            }
        } catch (\Exception $ex) {
            $this->throwRecoverableSoapProducerException($ex->getMessage(), $soapClient, $this->getDisplayResponseError(), $ex->getCode(), $ex);
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

        $paramsResolver->setDefault(self::DISPLAY_RESPONSE_ERROR, false);

        $paramsResolver->setDefined([
            self::SOAP_OPTIONS,
            self::DISPLAY_RESPONSE_ERROR,
            self::SOAP_HEADERS,
            self::HTTP_HEADERS,
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

        $this->setDisplayResponseError($params, $context);

        $requestName = $params[self::REQUEST_NAME];
        $soapMethodName = $this->confHelper->resolve($params[self::SOAP_METHOD_NAME], $context);
        $soapMethodParams = $this->confHelper->resolve($params[self::REQUEST_PARAMETERS], $context);
        $soapOptions = isset($params[self::SOAP_OPTIONS]) ? $this->confHelper->resolve($params[self::SOAP_OPTIONS], $context) : [];
        $soapHeaders = isset($params[self::SOAP_HEADERS]) ? $params[self::SOAP_HEADERS] : [];
        $httpHeaders = isset($params[self::HTTP_HEADERS]) ? $this->confHelper->resolve($params[self::HTTP_HEADERS], $context) : [];

        try {
            $soapOptions['connection_timeout'] = $endpointOptions[ConfigurableWebserviceProtocol::OPTION_CONNECT_TIMEOUT];
            if ($this->getSoapClient($endpointOptions)) {
                $httpHeaders[self::HTTP_HEADER_TRANSACTION_ID] = $context['msg']->getContext()['transaction_id'];
                $httpHeaders[self::HTTP_HEADER_EAI_TIMESTAMP] = $context['msg']->getContext()['timestamp'];
                $soapClient = $this->getSoapClient($endpointOptions);
                $soapClient->setRequestHeaders($httpHeaders);
            }
        } catch (\ErrorException $exception) {
            $this->throwRecoverableSoapFaultException($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, $exception);
        } catch (\SoapFault $exception) {
            $this->throwRecoverableSoapFaultException($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, $exception);
        }

        $result = $this->performRequest($soapMethodName, $soapMethodParams, $endpointOptions, $soapOptions, $soapHeaders);
        $this->getEventDispatcher()->dispatch(ExternalSystemHTTPEvent::EVENT_NAME, $this->getExternalSystemHTTPEvent($context, $endpointOptions));
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
     * Throw a RecoverableSoapFault when SoapClient construct failed.
     *
     * @param \SoapClient $soapClient
     * @param int         $code
     * @param null        $previousException
     *
     * @throws \Smartbox\Integration\FrameworkBundle\Components\WebService\Soap\Exceptions\RecoverableSoapException
     */
    protected function throwRecoverableSoapFaultException($message, $code = 0, $previousException = null)
    {
        /* @var \SoapClient $soapClient */
        $exception = new RecoverableSoapException(
            $message,
            null,
            null,
            null,
            null,
            $code,
            $previousException
        );
        $exception->setShowExternalSystemErrorMessage(true);
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

    /**
     * @param $context
     * @param $endpointOptions
     *
     * @return ExternalSystemHTTPEvent
     */
    public function getExternalSystemHTTPEvent(&$context, &$endpointOptions)
    {
        // Dispatch event with error information

        $client = $this->getSoapClient($endpointOptions);

        $event = new ExternalSystemHTTPEvent();
        $event->setEventDetails('HTTP SOAP Request/Response Event');
        $event->setTimestampToCurrent();
        $event->setExchangeId($context['exchange']->getId());
        $event->setTransactionId($context['msg']->getContext()['transaction_id']);
        $event->setFromUri($context['msg']->getContext()['from']);
        $event->setHttpURI($client->__getLastRequestUri());
        $event->setRequestHttpHeaders($this->parseHeadersToArray($client->__getLastRequestHeaders()));
        $event->setResponseHttpHeaders($this->parseHeadersToArray($client->__getLastResponseHeaders()));
        $event->setRequestHttpBody($client->__getLastRequest());
        $event->setResponseHttpBody($client->__getLastResponse());

        if (200 === $client->__getLastResponseCode()) {
            $event->setStatus('Success');
        } else {
            $event->setStatus('Error');
        }

        return $event;
    }
}
