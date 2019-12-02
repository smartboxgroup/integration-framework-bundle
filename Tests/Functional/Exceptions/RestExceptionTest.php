<?php


namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Exceptions;

use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\RestException;

class RestExceptionTest extends TestCase
{
    /**
     * @param $string
     * @param $expected
     *
     * @dataProvider provideExceptionData
     */
    public function testHandlingOfInvalidUTF8($string, $expected)
    {
        $restException = new RestException('Unused in test');
        $restException->setResponseHttpBody($string);

        $this->assertSame($expected, $restException->getResponseHttpBody());
    }

    public function provideExceptionData()
    {
        return [
            ['<html><head>Head</head><body>Body</body></html>', '<html><head>Head</head><body>Body</body></html>'],
            [str_split('🇮🇪')[0], '?'],
            ['valid string here ' . str_split('🇮🇪')[0], 'valid string here ?'],
        ];
    }
}