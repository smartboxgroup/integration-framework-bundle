<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Components\WebService\Rest;


use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\HttpErrorHandler;

class HttpErrorHandlerTest extends TestCase
{
    /**
     * @group httpErrorHandler
     *
     * @dataProvider provideInvalidConfigData
     */
    public function testInvalidArgument(array $config)
    {
        $this->expectException(\InvalidArgumentException::class);
        HttpErrorHandler::create($config);
    }

    /**
     * @group httpErrorHandler
     */
    public function testCreate()
    {
        $config = ['http_errors' => false];

        $stack = HttpErrorHandler::create($config);

        $this->assertInstanceOf(HandlerStack::class, $stack, 'Stack should be instance of ' . HandlerStack::class);
        $this->assertTrue($stack->hasHandler(), 'Handler was not assigned to the current stack.');
    }
    
    public function provideInvalidConfigData(): array
    {
        return [
            'NOT_PRESENT' => [[]],
            'TRUE' => [['http_errors' => true]]
        ];
    }
}
