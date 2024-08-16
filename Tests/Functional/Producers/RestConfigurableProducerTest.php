<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Producers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Smartbox\CoreBundle\Tests\Fixtures\Entity\SerializableThing;
use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\RestConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\RestConfigurableProtocol;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\BaseTestCase;

/**
 * Class RestConfigurableProducerTest.
 */
class RestConfigurableProducerTest extends BaseTestCase
{
    /** @var RestConfigurableProducer */
    protected $producer;

    public function setUp()
    {
        parent::setUp();
        $producer = new RestConfigurableProducer();
        $producer->setEvaluator(self::getContainer()->get('smartesb.util.evaluator'));
        $producer->setSerializer(self::getContainer()->get('jms_serializer'));
        $producer->setConfHelper(self::getContainer()->get('smartesb.configurable_service_helper'));
        $producer->setEventDispatcher(self::getContainer()->get('event_dispatcher'));

        $this->producer = $producer;
    }

    public function getCasesForBaseUri()
    {
        return [
            'default' => [
                'base_uri' => 'http://someservice.com/api/',
                'uri' => 'v0/put/cats',
                'overrideBaseUri' => false,
                'expectedHost' => 'someservice.com',
                'expectedPath' => '/api/v0/put/cats',
            ],
            'override' => [
                'base_uri' => 'http://someservice.com/api/',
                'uri' => 'v0/put/cats',
                'overrideBaseUri' => 'http://dev.machine/api/',
                'expectedHost' => 'dev.machine',
                'expectedPath' => '/api/v0/put/cats',
            ],
            'override but ignore null' => [
                'base_uri' => 'http://someservice.com/api/',
                'uri' => 'v0/put/cats',
                'overrideBaseUri' => null,
                'expectedHost' => 'someservice.com',
                'expectedPath' => '/api/v0/put/cats',
            ],
            'override but ignore evaluate to null' => [
                'base_uri' => 'http://someservice.com/api/',
                'uri' => 'v0/put/cats',
                'overrideBaseUri' => "eval: null",
                'expectedHost' => 'someservice.com',
                'expectedPath' => '/api/v0/put/cats',
            ],
        ];
    }

    /**
     * @param $baseUri
     * @param $requestUri
     * @param $overrideBaseUri
     * @param $expectedHost
     * @param $expectedPath
     * @dataProvider getCasesForBaseUri
     */
    public function testCanOverrideBaseUrI($baseUri, $requestUri, $overrideBaseUri, $expectedHost, $expectedPath)
    {
        $mockHandler = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar']),
        ]);

        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mockHandler);
        $stack->push($history);

        $client = new Client(['handler' => $stack]);

        $producer = $this->producer;
        $producer->setHttpClient($client);

        $actionParams = [
            RestConfigurableProducer::REQUEST_NAME => 'someRequest',
            RestConfigurableProducer::REQUEST_HTTP_VERB => 'POST',
            RestConfigurableProducer::REQUEST_BODY => ['hello' => 'world'],
            RestConfigurableProducer::REQUEST_URI => $requestUri,
            RestConfigurableProducer::DISPLAY_RESPONSE_ERROR => false,
        ];

        $options = [
            RestConfigurableProtocol::OPTION_BASE_URI => $baseUri,
            RestConfigurableProtocol::OPTION_ENCODING => RestConfigurableProtocol::ENCODING_JSON,
            ConfigurableWebserviceProtocol::OPTION_CONNECT_TIMEOUT => 10,
            ConfigurableWebserviceProtocol::OPTION_TIMEOUT => 10,
            RestConfigurableProtocol::OPTION_HEADERS => [],
            RestConfigurableProtocol::OPTION_AUTH => false,
        ];
        $context = [
            'vars' => [],
            'msg'  => new Message(new SerializableThing(), [], new Context()),
            'exchange' => new Exchange(),
        ];

        //Decide to override the base_uri or not
        if (false !== $overrideBaseUri) {
            $actionParams['base_uri'] = $overrideBaseUri;
        }

        $response = $producer->executeStep(RestConfigurableProducer::STEP_REQUEST, $actionParams, $options, $context);
        $this->assertTrue($response, 'The producer should return true to say it has completed the Request Step');

        /** @var Request $request */
        $request = $container[0]['request'];

        $this->assertEquals($expectedHost, $request->getUri()->getHost());
        $this->assertEquals($expectedPath, $request->getUri()->getPath());
    }

    public function testShouldAllowGetWithoutQuery()
    {
        $requestUri = 'v0/put/cats';
        $baseUri = 'http://someservice.com/api/';

        $mockHandler = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar']),
        ]);

        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mockHandler);
        $stack->push($history);

        $client = new Client(['handler' => $stack]);

        $producer = $this->producer;
        $producer->setHttpClient($client);

        $actionParams = [
            RestConfigurableProducer::REQUEST_NAME => 'someRequest',
            RestConfigurableProducer::REQUEST_HTTP_VERB => 'GET',
            RestConfigurableProducer::REQUEST_BODY => [],
            RestConfigurableProducer::REQUEST_URI => $requestUri,
            RestConfigurableProducer::DISPLAY_RESPONSE_ERROR => false,
        ];

        $options = [
            RestConfigurableProtocol::OPTION_BASE_URI => $baseUri,
            RestConfigurableProtocol::OPTION_ENCODING => RestConfigurableProtocol::ENCODING_JSON,
            ConfigurableWebserviceProtocol::OPTION_CONNECT_TIMEOUT => 10,
            ConfigurableWebserviceProtocol::OPTION_TIMEOUT => 10,
            RestConfigurableProtocol::OPTION_HEADERS => [],
            RestConfigurableProtocol::OPTION_AUTH => false,
        ];
        $context = [
            'vars' => [],
            'msg'  => new Message(new SerializableThing(), [], new Context()),
            'exchange' => new Exchange(),
        ];

        $response = $producer->executeStep(RestConfigurableProducer::STEP_REQUEST, $actionParams, $options, $context);
        $this->assertTrue($response, 'The producer should return true to say it has completed the Request Step');
    }
}
