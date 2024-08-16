<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use JMS\Serializer\Exception\RuntimeException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Smartbox\Integration\FrameworkBundle\Components\WebService\AbstractWebServiceProducer;
use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Exception\ExternalSystemExceptionInterface;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\RecoverableRestException;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\UnrecoverableRestException;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesGuzzleHttpClient;
use Smartbox\Integration\FrameworkBundle\Events\ExternalSystemHTTPEvent;
use Smartbox\Integration\FrameworkBundle\Exceptions\UnexpectedValueException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RestConfigurableProducer extends AbstractWebServiceProducer implements HttpClientInterface
{
    use UsesGuzzleHttpClient;

    const REQUEST_BODY = 'body';
    const REQUEST_QUERY_PARAMETERS = 'query';
    const REQUEST_NAME = 'name';
    const REQUEST_HTTP_VERB = 'http_method';
    const REQUEST_URI = 'uri';
    const REQUEST_BASE_URI = 'base_uri';
    const VALIDATION = 'validations';
    const VALIDATION_RULE = 'rule';
    const VALIDATION_MESSAGE = 'message';
    const VALIDATION_DISPLAY_MESSAGE = 'display_message';
    const VALIDATION_RECOVERABLE = 'recoverable';
    const REQUEST_EXPECTED_RESPONSE_TYPE = 'response_type';
    const REQUEST_EXPECTED_RESPONSE_FORMAT = 'response_format';

    /**
     * @param $options
     *
     * @return array
     */
    protected function getBasicHTTPOptions($options, array &$endpointOptions)
    {
        $result = [
            RequestOptions::CONNECT_TIMEOUT => $endpointOptions[ConfigurableWebserviceProtocol::OPTION_CONNECT_TIMEOUT],
            RequestOptions::TIMEOUT => $endpointOptions[ConfigurableWebserviceProtocol::OPTION_TIMEOUT],
            RequestOptions::HEADERS => array_merge(
                $endpointOptions[RestConfigurableProtocol::OPTION_HEADERS],
                array_key_exists(RestConfigurableProtocol::OPTION_HEADERS, $options) ? $options[RestConfigurableProtocol::OPTION_HEADERS] : []
            ),
        ];

        $auth = $endpointOptions[RestConfigurableProtocol::OPTION_AUTH];
        if (RestConfigurableProtocol::AUTH_BASIC === $auth) {
            $result['auth'] = [
                $endpointOptions[Protocol::OPTION_USERNAME],
                $endpointOptions[Protocol::OPTION_PASSWORD],
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function executeStep($stepAction, &$stepActionParams, &$options, array &$context)
    {
        if (!parent::executeStep($stepAction, $stepActionParams, $options, $context)) {
            switch ($stepAction) {
                case self::STEP_REQUEST:
                    $this->request($this->getHttpClient(), $stepActionParams, $options, $context);

                    return true;
            }
        }

        return false;
    }

    /**
     * @return \GuzzleHttp\Psr7\Response
     *
     * @throws RecoverableRestException
     * @throws UnrecoverableRestException
     */
    protected function request(ClientInterface $client, array $stepActionParams, array $endpointOptions, array &$context)
    {
        if (!is_array($stepActionParams)) {
            throw new InvalidConfigurationException("Step 'request' in AbstractConfigurableProducer expected an array as configuration");
        }

        $stepParamsResolver = new OptionsResolver();

        $stepParamsResolver->setRequired([
            self::REQUEST_NAME,
            self::REQUEST_HTTP_VERB,
            self::REQUEST_BODY,
            self::REQUEST_URI,
        ]);

        $stepParamsResolver->setDefault(self::DISPLAY_RESPONSE_ERROR, false);
        $stepParamsResolver->setDefault(self::REQUEST_EXPECTED_RESPONSE_TYPE, 'array');
        $stepParamsResolver->setDefined([
            RestConfigurableProtocol::OPTION_HEADERS,
            self::VALIDATION,
            self::REQUEST_QUERY_PARAMETERS,
            self::REQUEST_BASE_URI,
            self::REQUEST_EXPECTED_RESPONSE_FORMAT
        ]);

        $stepParamsResolver->setAllowedValues(self::REQUEST_HTTP_VERB, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);

        $params = $stepParamsResolver->resolve($stepActionParams);

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

        $name = $this->confHelper->resolve($params[self::REQUEST_NAME], $context);
        $httpMethod = $this->confHelper->resolve($params[self::REQUEST_HTTP_VERB], $context);
        $httpMethod = strtoupper($httpMethod);

        $endpointOptions = $this->confHelper->resolve($endpointOptions, $context);

        $overrideBaseUri = !empty($params[self::REQUEST_BASE_URI])
            ? $this->confHelper->resolve($params[self::REQUEST_BASE_URI], $context)
            : null;

        $baseURI = $overrideBaseUri ?? $endpointOptions[RestConfigurableProtocol::OPTION_BASE_URI];

        $resolvedURI = $baseURI.$this->confHelper->resolve($params[self::REQUEST_URI], $context);

        $requestHeaders = isset($params[RestConfigurableProtocol::OPTION_HEADERS]) ?
            $this->confHelper->resolve($params[RestConfigurableProtocol::OPTION_HEADERS], $context) :
            []
        ;

        $params[RestConfigurableProtocol::OPTION_HEADERS] = $requestHeaders;

        $restOptions = $this->getBasicHTTPOptions($params, $endpointOptions);

        $encoding = $endpointOptions[RestConfigurableProtocol::OPTION_ENCODING];

        $body = $this->confHelper->resolve($params[self::REQUEST_BODY], $context);
        $requestBody = '';
        if ($body) {
            $requestBody = $this->encodeRequestBody($encoding, $body);
        }
        $restOptions['body'] = $requestBody;

        if ('GET' == $httpMethod && key_exists(self::REQUEST_QUERY_PARAMETERS, $params)) {
            $queryParameters = $this->confHelper->resolve($params[self::REQUEST_QUERY_PARAMETERS], $context);
            if (!$queryParameters) {
                throw new InvalidConfigurationException("'parameters' entry in 'request' configuration is mandatory for GET HTTP method");
            }
            $restOptions['query'] = $queryParameters;
        }

        try {
            $requestHeaders[self::HTTP_HEADER_TRANSACTION_ID] = $context['msg']->getContext()['transaction_id'];
            $requestHeaders[self::HTTP_HEADER_EAI_TIMESTAMP] = $context['msg']->getContext()['timestamp'];
        } catch (\Exception $e) {
        }
        /* @var Response $response */
        $request = new Request($httpMethod, $resolvedURI, $requestHeaders);
        $response = null;
        try {
            $response = $client->send($request, $restOptions);
            $responseContent = $response->getBody()->getContents();
            $this->getEventDispatcher()->dispatch(ExternalSystemHTTPEvent::EVENT_NAME, $this->getExternalSystemHTTPEvent($context, $request, $requestBody, $response, $responseContent, $endpointOptions));
            // Tries to parse the body and convert it into an object
            $responseBody = null;
            if ($responseContent) {
                try {
                    $responseBody = $this->getSerializer()->deserialize(
                        $responseContent,
                        $params[self::REQUEST_EXPECTED_RESPONSE_TYPE],
                        $params[self::REQUEST_EXPECTED_RESPONSE_FORMAT] ?? $encoding
                    );
                } catch (RuntimeException $e) {
                    // Assume that the exception is one of the JSON exceptions that will kill the workers
                    if (RestConfigurableProtocol::ENCODING_JSON === $encoding && (JSON_ERROR_SYNTAX != json_last_error())) {
                        throw new UnexpectedValueException($e->getMessage());
                    }

                    if (false === $endpointOptions[RestConfigurableProtocol::OPTION_RESPONSE_FALLBACK_ON_ERROR]) {
                        throw $e;
                    }

                    trigger_error('Falling back to the original response payload when the deserialization fails and a custom response format isn\'t defined will throw an exception in v3.', E_USER_DEPRECATED);
                    $responseBody = $responseContent;
                }
            }

            $context[self::KEY_RESPONSES][$name] = [
                'statusCode' => $response->getStatusCode(),
                'body' => $responseBody,
                'headers' => $response->getHeaders(),
            ];

            // Validates response (if needed)
            foreach ($validationSteps as $validationStep) {
                $isValid = $this->confHelper->evaluateStringOrExpression($validationStep[self::VALIDATION_RULE], $context);
                if (!$isValid) {
                    $message = $this->confHelper->evaluateStringOrExpression($validationStep[self::VALIDATION_MESSAGE], $context);
                    $recoverable = $validationStep[self::VALIDATION_RECOVERABLE];
                    $showMessage = (
                        isset($validationStep[self::VALIDATION_DISPLAY_MESSAGE]) &&
                        true === $validationStep[self::VALIDATION_DISPLAY_MESSAGE]
                    );
                    if ($recoverable) {
                        $this->throwRecoverableRestProducerException($message, $request->getHeaders(), $requestBody, $response->getHeaders(), $responseContent, $response->getStatusCode(), $showMessage);
                    } else {
                        $this->throwUnrecoverableRestProducerException($message, $request->getHeaders(), $requestBody, $response->getHeaders(), $responseContent, $response->getStatusCode(), $showMessage);
                    }
                }
            }

            return $response;
        } catch (GuzzleException $e) {
            // manages request exceptions with sensible defaults:
            // * 400-499 status codes: unrecoverable
            // * 500-599 status codes: recoverable

            $response = null;
            if ($e instanceof RequestException) {
                $response = $e->getResponse();
            }

            if ($response) {
                $response->getBody()->rewind();
                $responseBody = $response->getBody()->getContents();
                $responseHeaders = $response->getHeaders();
                $statusCode = $response->getStatusCode();
            } else {
                $responseBody = 'No response found';
                $responseHeaders = [];
                $statusCode = 0;
            }

            $this->setDisplayResponseError($params, $context);

            if ($statusCode >= 400 && $statusCode <= 499) {
                $this->throwUnrecoverableRestProducerException($e->getMessage(), $request->getHeaders(), $requestBody, $responseHeaders, $responseBody, $statusCode, $this->getDisplayResponseError(), $e->getCode(), $e);
            } else {
                $this->throwRecoverableRestProducerException($e->getMessage(), $request->getHeaders(), $requestBody, $responseHeaders, $responseBody, $statusCode, $this->getDisplayResponseError(), $e->getCode(), $e);
            }
        } catch (UnrecoverableRestException $e) {
            throw $e;
        } catch (UnexpectedValueException $e) {
            /*
             * Assume that the UnexpectedValueException thrown has the potential to kill a worker, remove
             * the responseBody from the payload to keep the logger from terminating when it encounters an
             * exception during serialization.
             */
            $this->throwRecoverableRestProducerException($e->getMessage(), $request->getHeaders(), $requestBody, $response->getHeaders(), null, $response ? $response->getStatusCode() : 0, false, $e->getCode(), $e);
        } catch (\Exception $e) {
            if ($response) {
                $response->getBody()->rewind();
                $responseBody = $response->getBody()->getContents();
                $responseHeaders = $response->getHeaders();
            } else {
                $responseBody = 'No response found';
                $responseHeaders = [];
            }
            $showMessage = ($e instanceof ExternalSystemExceptionInterface && $e->mustShowExternalSystemErrorMessage());
            $this->throwRecoverableRestProducerException($e->getMessage(), $request->getHeaders(), $requestBody, $responseHeaders, $responseBody, $response ? $response->getStatusCode() : 0, $showMessage, $e->getCode(), $e);
        }
    }

    /**
     * @param $encoding
     * @param $rawBody
     *
     * @return mixed
     */
    protected function encodeRequestBody($encoding, $rawBody)
    {
        return $this->getSerializer()->serialize($rawBody, $encoding);
    }

    /**
     * @param $message
     * @param string     $requestBody
     * @param array      $responseHeaders
     * @param string     $responseBody
     * @param int        $responseStatusCode
     * @param bool       $showMessage
     * @param int        $code
     * @param \Exception $previous
     *
     * @throws RecoverableRestException
     */
    public function throwRecoverableRestProducerException(
        $message,
        array $requestHeaders = [],
        $requestBody = '',
        $responseHeaders = [],
        $responseBody = '',
        $responseStatusCode = 0,
        $showMessage = false,
        $code = 0,
        \Exception $previous = null
    ) {
        $exception = new RecoverableRestException($message, $requestHeaders, $requestBody, $responseHeaders, $responseBody, $responseStatusCode, $code, $previous);
        $exception->setExternalSystemName($this->getName());
        $exception->setShowExternalSystemErrorMessage($showMessage);
        throw $exception;
    }

    /**
     * @param $message
     * @param string     $requestBody
     * @param array      $responseHeaders
     * @param string     $responseBody
     * @param int        $responseStatusCode
     * @param bool       $showMessage
     * @param int        $code
     * @param \Exception $previous
     *
     * @throws UnrecoverableRestException
     */
    public function throwUnrecoverableRestProducerException(
        $message,
        array $requestHeaders = [],
        $requestBody = '',
        $responseHeaders = [],
        $responseBody = '',
        $responseStatusCode = 0,
        $showMessage = false,
        $code = 0,
        \Exception $previous = null
    ) {
        $exception = new UnrecoverableRestException($message, $requestHeaders, $requestBody, $responseHeaders, $responseBody, $responseStatusCode, $code, $previous);
        $exception->setExternalSystemName($this->getName());
        $exception->setShowExternalSystemErrorMessage($showMessage);
        throw $exception;
    }

    public function getExternalSystemHTTPEvent($context, RequestInterface $request, $requestBody, ResponseInterface $response, $restClientResponse, $endpointOptions)
    {
        $event = new ExternalSystemHTTPEvent();
        $event->setEventDetails('HTTP REST Request/Response Event');
        $event->setTimestampToCurrent();
        $event->setHttpURI($request->getUri()->__toString());
        $event->setRequestHttpHeaders($request->getHeaders());
        $event->setResponseHttpHeaders($response->getHeaders());
        $event->setFromUri($context['msg']->getContext()['from']);
        $event->setExchangeId($context['exchange']->getId());
        $event->setTransactionId($context['msg']->getContext()['transaction_id']);
        $event->setResponseHttpBody($restClientResponse);
        $event->setRequestHttpBody($requestBody);

        if (200 === $response->getStatusCode()) {
            $event->setStatus('Success');
        } else {
            $event->setStatus('Error');
        }

        return $event;
    }
}
