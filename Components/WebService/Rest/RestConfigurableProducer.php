<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\RecoverableRestException;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\UnrecoverableRestException;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesGuzzleHttpClient;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RestConfigurableProducer
 */
class RestConfigurableProducer extends ConfigurableProducer
{
    use UsesGuzzleHttpClient;

    const REQUEST_BODY = 'body';
    const REQUEST_NAME = 'name';
    const REQUEST_HTTP_VERB = 'http_method';
    const REQUEST_URI = 'uri';
    const VALIDATION = 'validations';
    const VALIDATION_RULE = 'rule';
    const VALIDATION_MESSAGE = 'message';
    const VALIDATION_RECOVERABLE = 'recoverable';
    const REQUEST_EXPECTED_RESPONSE_TYPE = 'response_type';

    /**
     * @param       $options
     * @param array $options
     *
     * @return array
     */
    protected function getBasicHTTPOptions($options, array &$options)
    {
        $result = [
            RequestOptions::CONNECT_TIMEOUT => $options[ConfigurableWebserviceProtocol::OPTION_CONNECT_TIMEOUT],
            RequestOptions::TIMEOUT => $options[ConfigurableWebserviceProtocol::OPTION_TIMEOUT],
            RequestOptions::HEADERS => $options[RestConfigurableProtocol::OPTION_HEADERS],
        ];

        $auth = $options[RestConfigurableProtocol::OPTION_AUTH];
        if ($auth === RestConfigurableProtocol::AUTH_BASIC) {
            $result['auth'] = [
                $options[Protocol::OPTION_USERNAME],
                $options[Protocol::OPTION_PASSWORD], ];
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
     */
    protected function request(ClientInterface $client, array $stepActionParams, array $endpointOptions, array &$context)
    {
        if (!is_array($stepActionParams)) {
            throw new InvalidConfigurationException(
                "Step 'request' in ConfigurableProducer expected an array as configuration"
            );
        }

        $stepParamsResolver = new OptionsResolver();

        $stepParamsResolver->setRequired([
                self::REQUEST_NAME,
                self::REQUEST_HTTP_VERB,
                self::REQUEST_BODY,
                self::REQUEST_URI
        ]);

        $stepParamsResolver->setDefault(self::REQUEST_EXPECTED_RESPONSE_TYPE,'array');
        $stepParamsResolver->setDefined([
            RestConfigurableProtocol::OPTION_HEADERS,
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

            foreach($params[self::VALIDATION] as $validation) {
                $validationSteps[] = $validationParamsResolver->resolve($validation);
            }
        }

        $name = $this->resolve($params[self::REQUEST_NAME], $context);
        $httpMethod = $this->resolve($params[self::REQUEST_HTTP_VERB], $context);
        $body = $this->resolve($params[self::REQUEST_BODY], $context);

        $resolvedURI = $endpointOptions[RestConfigurableProtocol::OPTION_BASE_URI];
        $resolvedURI .= $this->resolve($params[self::REQUEST_URI], $context);

        $restOptions = $this->getBasicHTTPOptions($params, $endpointOptions);

        $encoding = $endpointOptions[RestConfigurableProtocol::OPTION_ENCODING];
        $restOptions['body'] = $this->getSerializer()->serialize($body, $encoding);

        $httpMethod = strtoupper($httpMethod);

        /* @var Response $response */
        $request = new Request($httpMethod, $resolvedURI, $params[RestConfigurableProtocol::OPTION_HEADERS]);
        $response = $client->send($request, $restOptions);
        $responseContent = $response->getBody()->getContents();

        $context[self::KEY_RESPONSES][$name] = [
            'statusCode' => $response->getStatusCode(),
            'body' => $this->getSerializer()->deserialize(
                $responseContent,
                $params[self::REQUEST_EXPECTED_RESPONSE_TYPE],
                $encoding
            ),
            'headers' => $response->getHeaders(),
        ];

        // Validates response (if needed)
        foreach ($validationSteps as $validationStep) {
            $isValid = $this->evaluateStringOrExpression($validationStep[self::VALIDATION_RULE], $context);
            if (!$isValid) {
                $message = $this->evaluateStringOrExpression($validationStep[self::VALIDATION_MESSAGE], $context);
                $recoverable = $validationStep[self::VALIDATION_RECOVERABLE];

                if ($recoverable) {
                    $this->throwRecoverableRestProducerException($message, $request, $response);
                } else {
                    $this->throwUnrecoverableRestProducerException($message, $request, $response);
                }
            }
        }

        return $response;
    }

    /**
     * @param string                              $message
     * @param \Psr\Http\Message\RequestInterface  $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param int                                 $code
     * @param \Exception|null                     $previousException
     *
     * @throws RecoverableRestException
     */
    public function throwRecoverableRestProducerException(
        $message,
        RequestInterface $request,
        ResponseInterface $response,
        $code = 0,
        \Exception $previousException = null
    ){
        throw new RecoverableRestException($message, $request, $response, $code, $previousException);
    }

    /**
     * @param string                              $message
     * @param \Psr\Http\Message\RequestInterface  $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param int                                 $code
     * @param \Exception|null                     $previousException
     *
     * @throws UnrecoverableRestException
     */
    public function throwUnrecoverableRestProducerException(
        $message,
        RequestInterface $request,
        ResponseInterface $response,
        $code = 0,
        \Exception $previousException = null
    ){
        throw new UnrecoverableRestException($message, $request, $response, $code, $previousException);
    }
}
