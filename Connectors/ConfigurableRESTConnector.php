<?php
namespace Smartbox\Integration\FrameworkBundle\Connectors;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Smartbox\Integration\FrameworkBundle\Traits\UsesGuzzleHttpClient;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigurableRESTConnector extends ConfigurableConnector
{
    use UsesGuzzleHttpClient;

    const AUTH_BASIC = 'basic';
    const OPTION_AUTH = 'authentication';
    const OPTION_ENCODING = 'encoding';
    const OPTION_BASE_URI = 'base_uri';
    const OPTION_HEADERS = 'headers';

    const REQUEST_BODY = 'body';
    const REQUEST_NAME = 'name';
    const REQUEST_HTTP_VERB = 'http_method';
    const REQUEST_URI = 'uri';

    const ENCODING_JSON = 'json';
    const ENCODING_XML = 'xml';

    protected function getBasicHTTPOptions($options, array &$context)
    {
        return [
            'connect_timeout' => $options[self::OPTION_TIMEOUT]
        ];
    }

    protected function request(array $stepActionParams, array $connectorOptions, array &$context)
    {
        if (!is_array($stepActionParams)) {
            throw new InvalidConfigurationException(
                "Step 'request' in ConfigurableConnector expected an array as configuration"
            );
        }

        $this->optionsResolver->setRequired(
            [self::REQUEST_NAME, self::REQUEST_HTTP_VERB, self::REQUEST_BODY, self::REQUEST_URI, self::OPTION_ENCODING]
        );

        $this->optionsResolver->setAllowedValues(self::REQUEST_HTTP_VERB, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);

        $executionOptions = $this->optionsResolver->resolve($stepActionParams);

        $name = $executionOptions[self::REQUEST_NAME];
        $httpMethod = $executionOptions[self::REQUEST_HTTP_VERB];
        $body = $this->resolve($executionOptions[self::REQUEST_BODY], $context);
        $headers = $this->resolve($executionOptions[self::OPTION_HEADERS], $context);
        $resolvedURI = $executionOptions[self::OPTION_BASE_URI];
        $resolvedURI .= $this->replaceTemplateVars($executionOptions[self::REQUEST_URI], $context);
        $auth = $executionOptions[self::OPTION_AUTH];

        // Init rest client if not done
        if(!$this->getHttpClient()){
            $restClient = new Client([
                'timeout' => 0,
                'allow_redirects' => false
            ]);
            $this->setHttpClient($restClient);
        }

        $restOptions = $this->getBasicHTTPOptions($executionOptions, $context);
        $restOptions['body'] = $this->getSerializer()->serialize($body, $executionOptions['encoding']);

        if($auth === self::AUTH_BASIC){
            $restOptions['auth'] = [
                $executionOptions[self::OPTION_USERNAME],
                $executionOptions[self::OPTION_PASSWORD]];
        }

        if (!empty($headers)) {
            $restOptions['headers'] = $headers;
        }

        $httpMethod = strtoupper($httpMethod);

        /** @var Response $response */
        $response = $this->getHttpClient()->request($httpMethod, $resolvedURI, $restOptions);

        $context[self::KEY_RESPONSES][$name] = [
            'statusCode' => $response->getStatusCode(),
            'body' => $this->getSerializer()->deserialize(
                $response->getBody()->getContents(),
                'array',
                $executionOptions['encoding']
            ),
            'headers' => $response->getHeaders(),
        ];
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
                self::OPTION_BASE_URI => ['Base URI for all requests', []],
                self::OPTION_HEADERS => ['Default headers to include in all requests (key-value array)', []],
                self::OPTION_ENCODING => [
                    'Encoding for requests and responses with the REST API',
                    [
                        self::ENCODING_JSON => 'Json encoding',
                        self::ENCODING_XML => 'Xml encoding',
                    ]
                ],
            ]
        );

    }
}