<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Components\WebService\Soap;

use Smartbox\Integration\FrameworkBundle\Components\WebService\Soap\ParseHeadersTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ParseHeadersTraitTest extends TestCase
{
    /**
     * @var
     */
    private $parseTrait;

    /**
     * @var array
     */
    private $httpMethods = [
        Request::METHOD_POST,
        Request::METHOD_CONNECT,
        Request::METHOD_DELETE,
        Request::METHOD_GET,
        Request::METHOD_HEAD,
        Request::METHOD_OPTIONS,
        Request::METHOD_PURGE,
        Request::METHOD_PATCH,
        Request::METHOD_PUT,
        Request::METHOD_TRACE,
    ];

    public function setUp(): void
    {
        $this->parseTrait = $this->getMockForTrait(ParseHeadersTrait::class);
    }

    /**
     * @group parseSoapHeaders
     * @dataProvider provideParseableData
     */
    public function testParseHeadersToArray($header, $expected)
    {
        $result = $this->parseTrait->parseHeadersToArray($header);

        $this->assertIsArray($result, 'The parser should return an array');
        $this->assertNotEmpty($result, 'The parser should not return an empty array');
        $this->assertEquals($expected, $result, 'The parser did not returned the same as expected.');
    }

    /**
     * @group parseSoapHeaders
     * @dataProvider provideUnparseableData
     */
    public function testUnParseHeadersToArray($data)
    {
        $result = $this->parseTrait->parseHeadersToArray($data);

        $this->assertIsArray($result, 'The parser should return an array');
        $this->assertTrue(\is_array($result), 'The parser should return an array');
        $this->assertCount(0, $result, 'The parser should return an empty array');
    }

    /**
     * @return array
     */
    public function provideParseableData()
    {
        return [
            [
                "Content-Type: text/html; charset=utf-8;\r\nAccept: */*",
                [
                    'Content-Type' => 'text/html; charset=utf-8;',
                    'Accept' => '*/*',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function provideUnparseableData()
    {
        $methods = [];

        foreach ($this->httpMethods as $method) {
            $methods['do_not_parse_'.$method] = "$method http://any.url.com/with/random/method";
        }

        return [$methods];
    }
}
