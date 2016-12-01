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
use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Exception\ExternalSystemExceptionInterface;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\RecoverableRestException;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\UnrecoverableRestException;
use Smartbox\Integration\FrameworkBundle\Core\Producers\AbstractConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesGuzzleHttpClient;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RestConfigurableProducer.
 */
class RestConfigurableProducer extends AbstractConfigurableProducer
{
    use UsesGuzzleHttpClient;

    const REQUEST_BODY = 'body';
    const REQUEST_NAME = 'name';
    const REQUEST_HTTP_VERB = 'http_method';
    const REQUEST_URI = 'uri';
    const VALIDATION = 'validations';
    const VALIDATION_RULE = 'rule';
    const VALIDATION_MESSAGE = 'message';
    const VALIDATION_DISPLAY_MESSAGE = 'display_message';
    const VALIDATION_RECOVERABLE = 'recoverable';
    const REQUEST_EXPECTED_RESPONSE_TYPE = 'response_type';

    /**
     * @param       $options
     * @param array $endpointOptions
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
        if ($auth === RestConfigurableProtocol::AUTH_BASIC) {
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
     * @param \GuzzleHttp\ClientInterface $client
     * @param array                       $stepActionParams
     * @param array                       $endpointOptions
     * @param array                       $context
     *
     * @return \GuzzleHttp\Psr7\Response
     *
     * @throws RecoverableRestException
     * @throws UnrecoverableRestException
     */
    protected function request(ClientInterface $client, array $stepActionParams, array $endpointOptions, array &$context)
    {
        if (!is_array($stepActionParams)) {
            throw new InvalidConfigurationException(
                "Step 'request' in AbstractConfigurableProducer expected an array as configuration"
            );
        }

        $stepParamsResolver = new OptionsResolver();

        $stepParamsResolver->setRequired([
            self::REQUEST_NAME,
            self::REQUEST_HTTP_VERB,
            self::REQUEST_BODY,
            self::REQUEST_URI,
        ]);

        $stepParamsResolver->setDefault(self::REQUEST_EXPECTED_RESPONSE_TYPE, 'array');
        $stepParamsResolver->setDefined([
            RestConfigurableProtocol::OPTION_HEADERS,
            self::VALIDATION,
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
        $body = $this->confHelper->resolve($params[self::REQUEST_BODY], $context);

        $resolvedURI = $endpointOptions[RestConfigurableProtocol::OPTION_BASE_URI];
        $resolvedURI .= $this->confHelper->resolve($params[self::REQUEST_URI], $context);

        $endpointOptions = $this->confHelper->resolve($endpointOptions, $context);

        $restOptions = $this->getBasicHTTPOptions($params, $endpointOptions);

        $encoding = $endpointOptions[RestConfigurableProtocol::OPTION_ENCODING];
        $restOptions['body'] = $this->encodeRequestBody($encoding, $body);

        $httpMethod = strtoupper($httpMethod);
        $requestHeaders = isset($params[RestConfigurableProtocol::OPTION_HEADERS]) ?
            $params[RestConfigurableProtocol::OPTION_HEADERS] :
            []
        ;

        /* @var Response $response */
        $request = new Request($httpMethod, $resolvedURI, $requestHeaders);
        $response = null;
        try {
            $response = $client->send($request, $restOptions);
            $responseContent = $response->getBody()->getContents();

            // Tries to parse the body and convert it into an object
            $responseBody = null;
            if ($responseContent) {
                try {
                    $responseBody = $this->getSerializer()->deserialize(
                        $responseContent,
                        $params[self::REQUEST_EXPECTED_RESPONSE_TYPE],
                        $encoding
                    );
                } catch (RuntimeException $e) {
                    // if it cannot parse the response fallback to the textual content of the body
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
                        $this->throwRecoverableRestProducerException($message, $request, $response, $showMessage);
                    } else {
                        $this->throwUnrecoverableRestProducerException($message, $request, $response, $showMessage);
                    }
                }
            }

            return $response;
        } catch (GuzzleException $e) {
            // manages request exceptions with sensible defaults:
            // * 400-499 status codes: unrecoverable
            // * 500-599 status codes: recoverable

            $response = null;
            $statusCode = $e->getCode();
            if ($e instanceof RequestException) {
                $response = $e->getResponse();
            }

            if ($request) {
                $request->getBody()->rewind();
            }

            if ($response) {
                $response->getBody()->rewind();
            }

            if ($statusCode >= 400 && $statusCode <= 499) {
                $this->throwUnrecoverableRestProducerException($e->getMessage(), $request, $response, false, $statusCode, $e);
            } else {
                $this->throwRecoverableRestProducerException($e->getMessage(), $request, $response, false, $statusCode, $e);
            }
        } catch (UnrecoverableRestException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($response) {
                $response->getBody()->rewind();
            }
            $showMessage = ($e instanceof ExternalSystemExceptionInterface && $e->mustShowExternalSystemErrorMessage());
            $this->throwRecoverableRestProducerException($e->getMessage(), $request, $response, $showMessage, $response ? $response->getStatusCode() : null, $e);
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
     * @param string                                   $message
     * @param \Psr\Http\Message\RequestInterface       $request
     * @param \Psr\Http\Message\ResponseInterface|null $response
     * @param bool                                     $showMessage
     * @param int                                      $code
     * @param \Exception|null                          $previousException
     *
     * @throws \Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\RecoverableRestException
     */
    public function throwRecoverableRestProducerException(
        $message,
        RequestInterface $request,
        ResponseInterface $response = null,
        $showMessage = false,
        $code = 0,
        \Exception $previousException = null
    ) {
        $exception = new RecoverableRestException($message, $request, $response, $code, $previousException);
        $exception->setExternalSystemName($this->getName());
        $exception->setShowExternalSystemErrorMessage($showMessage);
        throw $exception;
    }

    /**
     * @param string                                   $message
     * @param \Psr\Http\Message\RequestInterface       $request
     * @param \Psr\Http\Message\ResponseInterface|null $response
     * @param bool                                     $showMessage
     * @param int                                      $code
     * @param \Exception|null                          $previousException
     *
     * @throws \Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\UnrecoverableRestException
     */
    public function throwUnrecoverableRestProducerException(
        $message,
        RequestInterface $request,
        ResponseInterface $response = null,
        $showMessage = false,
        $code = 0,
        \Exception $previousException = null
    ) {
        $exception = new UnrecoverableRestException($message, $request, $response, $code, $previousException);
        $exception->setExternalSystemName($this->getName());
        $exception->setShowExternalSystemErrorMessage($showMessage);
        throw $exception;
    }
}
