<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;
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
                array_key_exists(RestConfigurableProtocol::OPTION_HEADERS, $options)? $options[RestConfigurableProtocol::OPTION_HEADERS] : []
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
     */
    protected function request(ClientInterface $client, array $stepActionParams, array $endpointOptions, array &$context)
    {
        if (!is_array($stepActionParams)) {
            throw new InvalidConfigurationException(
                "Step 'request' in ConfigurableProducer expected an array as configuration"
            );
        }

        $stepParamsResolver = new OptionsResolver();

        $stepParamsResolver->setRequired(
            [self::REQUEST_NAME, self::REQUEST_HTTP_VERB, self::REQUEST_BODY, self::REQUEST_URI]
        );
        $stepParamsResolver->setDefined([
            RestConfigurableProtocol::OPTION_HEADERS,
        ]);

        $stepParamsResolver->setAllowedValues(self::REQUEST_HTTP_VERB, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);

        $resolvedParams = $stepParamsResolver->resolve($stepActionParams);

        $name = $this->resolve($resolvedParams[self::REQUEST_NAME], $context);
        $httpMethod = $this->resolve($resolvedParams[self::REQUEST_HTTP_VERB], $context);
        $body = $this->resolve($resolvedParams[self::REQUEST_BODY], $context);

        $resolvedURI = $endpointOptions[RestConfigurableProtocol::OPTION_BASE_URI];
        $resolvedURI .= $this->resolve($resolvedParams[self::REQUEST_URI], $context);

        $restOptions = $this->getBasicHTTPOptions($resolvedParams, $endpointOptions);

        $encoding = $endpointOptions[RestConfigurableProtocol::OPTION_ENCODING];
        $restOptions['body'] = $this->getSerializer()->serialize($body, $encoding);

        $httpMethod = strtoupper($httpMethod);

        /* @var Response $response */
        $request = new Request($httpMethod, $resolvedURI);
        $response = $client->send($request, $restOptions);
        $responseContent = $response->getBody()->getContents();

        $context[self::KEY_RESPONSES][$name] = [
            'statusCode' => $response->getStatusCode(),
            'body' => $this->getSerializer()->deserialize(
                $responseContent,
                'array',
                $encoding
            ),
            'headers' => $response->getHeaders(),
        ];
    }
}
