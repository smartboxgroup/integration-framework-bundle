<?php
namespace Smartbox\Integration\FrameworkBundle\Producers;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Smartbox\Integration\FrameworkBundle\Traits\UsesGuzzleHttpClient;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class RESTConfigurableProducer extends ConfigurableProducer
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
            RequestOptions::CONNECT_TIMEOUT => $options[self::OPTION_CONNECT_TIMEOUT],
            RequestOptions::TIMEOUT => $options[self::OPTION_TIMEOUT],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function executeStep($stepAction, $stepActionParams, $options, array &$context)
    {
        if(!parent::executeStep($stepAction,$stepActionParams,$options,$context)){
            switch ($stepAction){
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
     * @param array                       $producerOptions
     * @param array                       $context
     */
    protected function request(ClientInterface $client, array $stepActionParams, array $producerOptions, array &$context)
    {
        if (!is_array($stepActionParams)) {
            throw new InvalidConfigurationException(
                "Step 'request' in ConfigurableProducer expected an array as configuration"
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
        $request = new Request($httpMethod, $resolvedURI, $headers);
        $response = $client->send($request, $restOptions);
        $responseContent = $response->getBody()->getContents();

        $context[self::KEY_RESPONSES][$name] = [
            'statusCode' => $response->getStatusCode(),
            'body' => $this->getSerializer()->deserialize(
                $responseContent,
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
                        self::ENCODING_JSON => 'JSON encoding',
                        self::ENCODING_XML => 'XML encoding',
                    ]
                ],
            ]
        );

    }
}
