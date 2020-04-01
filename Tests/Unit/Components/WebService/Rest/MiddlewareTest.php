<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Components\WebService\Rest;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\HttpErrorHandler;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Middleware;

class MiddlewareTest extends TestCase
{
    /**
     * @dataProvider provideResponseData
     */
    public function testHandleResponseBody(string $responseContent, string $expected, int $truncateAt = 120)
    {
       $response = new Response(400, [], $responseContent);

       Middleware::$truncateResponseSize = $truncateAt;

       $summary = Middleware::handleResponseBody($response);

       $this->assertEquals($summary, $expected);

    }
    
    public function testHttpErrors()
    {
        $responseContent = "Relationship type 'Experience-Component' cannot be applied on product type 'Experience' of child product 'Chasse au trésor au Centre Pompidou'.";

        $mock = new MockHandler([
            new Response(400, [], $responseContent)
        ]);

        $handlerStack = HandlerStack::create($mock);

        $handlerStack->push(Middleware::httpErrors(), 'http_errors_handler_test');

        $client = new Client(['handler' => $handlerStack, 'truncate_response_size'=> 30]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Relationship type 'Experience- (truncated...)");

        $client->request('GET', '/');
        
    }

    public function provideResponseData()
    {
        $response = "Relationship type 'Experience-Component' cannot be applied on product type 'Experience' of child product 'Chasse au trésor au Centre Pompidou'.";
        $fullResponse = "Relationship type 'Experience-Component' cannot be applied on product type 'Experience' of child product 'Chasse au trésor au Centre Pompidou'.";
        $defaultTruncate = "Relationship type 'Experience-Component' cannot be applied on product type 'Experience' of child product 'Chasse au trés (truncated...)";
        $truncatedAt135 = "Relationship type 'Experience-Component' cannot be applied on product type 'Experience' of child product 'Chasse au trésor au Centre Po (truncated...)";

        return [
            'Full_response' => [
                $response, $fullResponse, 0
            ],
            'Default' => [
                $response, $defaultTruncate
            ],
            'Truncated_at_135' => [
                $response, $truncatedAt135, 135
            ]
        ];
    }
}
