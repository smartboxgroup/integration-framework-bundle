<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Components\WebService\Rest;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use JMS\Serializer\SerializerInterface;
use Smartbox\CoreBundle\Tests\Fixtures\Entity\SerializableThing;
use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\RecoverableRestException;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\RestException;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\UnrecoverableRestException;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\RestConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\RestConfigurableProtocol;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class RestConfigurableProducerTest.
 */
class RestConfigurableProducerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $client;

    /** @var ExpressionEvaluator|\PHPUnit_Framework_MockObject_MockObject */
    protected $evaluator;

    /** @var SerializerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $serializer;

    /** @var RestConfigurableProducer */
    protected $producer;

    /** @var ConfigurableServiceHelper */
    private $helper;

    /** @var EventDispatcher */
    private $eventDispatcher;

    public function setUp()
    {
        $this->client = $this->getMockBuilder(ClientInterface::class)->getMock();
        $this->evaluator = $this->getMockBuilder(ExpressionEvaluator::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->evaluator->method('evaluateWithVars')
            ->with($this->anything(), $this->anything())
            ->willReturnArgument(0)
        ;
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $this->serializer->method('serialize')
            ->with($this->anything(), RestConfigurableProtocol::ENCODING_JSON, $this->anything())
            ->willReturnCallback(function ($data) {
                return \json_encode($data);
            })
        ;

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->helper = new ConfigurableServiceHelper();
        $this->helper->setEvaluator($this->evaluator);
        $this->helper->setSerializer($this->serializer);

        $this->producer = new RestConfigurableProducer();
        $this->producer->setHttpClient($this->client);
        $this->producer->setEvaluator($this->evaluator);
        $this->producer->setSerializer($this->serializer);
        $this->producer->setConfHelper($this->helper);
        $this->producer->setName('TestSystem');
    }

    /**
     * @param int              $statusCode
     * @param RequestException $internalException
     * @param string           $expectedException
     * @dataProvider getExpectedExceptionsForStatusCodes
     */
    public function testItShouldCreateProperRestExceptions(
        $statusCode,
        RequestException $internalException,
        $expectedException
    ) {
        $this->client->method('send')
            ->with($this->anything())
            ->willThrowException($internalException)
        ;

        $requestParams = [
            RestConfigurableProducer::REQUEST_NAME => 'someRequest',
            RestConfigurableProducer::REQUEST_HTTP_VERB => 'POST',
            RestConfigurableProducer::REQUEST_BODY => ['hello' => 'world'],
            RestConfigurableProducer::REQUEST_URI => 'something',
            RestConfigurableProducer::DISPLAY_RESPONSE_ERROR => false,
        ];

        $options = [
            RestConfigurableProtocol::OPTION_BASE_URI => 'http://someservice.com/api/',
            RestConfigurableProtocol::OPTION_ENCODING => RestConfigurableProtocol::ENCODING_JSON,
            ConfigurableWebserviceProtocol::OPTION_CONNECT_TIMEOUT => 10,
            ConfigurableWebserviceProtocol::OPTION_TIMEOUT => 10,
            RestConfigurableProtocol::OPTION_HEADERS => [],
            RestConfigurableProtocol::OPTION_AUTH => false,
        ];
        $context = ['vars' => []];

        /** @var RestException $exception */
        $exception = null;
        try {
            $this->producer->executeStep(RestConfigurableProducer::STEP_REQUEST, $requestParams, $options, $context);
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf($expectedException, $exception);
        $this->assertEquals('TestSystem', $exception->getExternalSystemName());
        /** @var RequestException $originalException */
        $originalException = $exception->getPrevious();
        $this->assertEquals($statusCode, $originalException->getResponse()->getStatusCode());
    }

    public function getCasesForBaseUri()
    {
        return [
            'default' => [
                'base_uri' => 'http://someservice.com/api/',
                'uri' => 'v0/put/cats',
                'overRideBaseUri' => false,
                'expectedHost' => 'someservice.com',
                'expectedPath' => '/api/v0/put/cats',
            ],
            'override' => [
                'base_uri' => 'http://someservice.com/api/',
                'uri' => 'v0/put/cats',
                'overRideBaseUri' => 'http://dev.machine/api/',
                'expectedHost' => 'dev.machine',
                'expectedPath' => '/api/v0/put/cats',
            ],
            'override but ignore null' => [
                'base_uri' => 'http://someservice.com/api/',
                'uri' => 'v0/put/cats',
                'overRideBaseUri' => null,
                'expectedHost' => 'someservice.com',
                'expectedPath' => '/api/v0/put/cats',
            ],
        ];
    }

    /**
     * @param $baseUri
     * @param $requestUri
     * @param $overRideBaseUri
     * @param $expectedHost
     * @param $expectedPath
     * @dataProvider getCasesForBaseUri
     */
    public function testCanOverrideBaseUrI($baseUri, $requestUri, $overRideBaseUri, $expectedHost, $expectedPath)
    {
        $mockHandler = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar']),
        ]);

        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mockHandler);
        $stack->push($history);

        $client = new Client(['handler' => $stack]);

        $producer = new RestConfigurableProducer();
        $producer->setHttpClient($client);
        $producer->setEvaluator($this->evaluator);
        $producer->setSerializer($this->serializer);
        $producer->setConfHelper($this->helper);
        $producer->setConfHelper($this->helper);
        $producer->setEventDispatcher($this->eventDispatcher);

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
        if (false !== $overRideBaseUri) {
            $actionParams['base_uri'] = $overRideBaseUri;
        }

        $response = $producer->executeStep(RestConfigurableProducer::STEP_REQUEST, $actionParams, $options, $context);
        $this->assertTrue($response, 'The producer should return true to say it has completed the Request Step');

        /** @var Request $request */
        $request = $container[0]['request'];

        $this->assertEquals($expectedHost, $request->getUri()->getHost());
        $this->assertEquals($expectedPath, $request->getUri()->getPath());
    }

    public function getExpectedExceptionsForStatusCodes()
    {
        $sampleRequest = new Request('POST', 'somewhere');

        return [
            [400, new RequestException('Bad request: https://http.cat/400', $sampleRequest, new Response(400)), UnrecoverableRestException::class],
            [418, new RequestException('I\'m a teapot: https://http.cat/418', $sampleRequest, new Response(418)), UnrecoverableRestException::class],
            [500, new ServerException('Internal server error: https://http.cat/500', $sampleRequest, new Response(500)), RecoverableRestException::class],
            [599, new ServerException('Network connection timeout error: https://http.cat/599', $sampleRequest, new Response(599)), RecoverableRestException::class],
        ];
    }
}
