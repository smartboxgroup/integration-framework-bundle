<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Tools\Mapper;

use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator;
use Smartbox\Integration\FrameworkBundle\Tools\Mapper\Mapper;

class MapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var Mapper */
    private $mapper;


    protected function setUp()
    {
        $this->mapper = new Mapper();
    }

    protected function tearDown()
    {
        $this->mapper = null;
    }

    /**
     * @return array
     */
    public function wordWrapDataProvider()
    {
        return [
            ["I'm a very long string, with words, numbers from 1 to 10 12334566 and long Supercalifragilisticexpialidocious", 12, 1, "I'm a very"],
            ["I'm a very long string, with words, numbers from 1 to 10 12334566 and long Supercalifragilisticexpialidocious", 12, 100, ''],
            ["I'm a very long string, with words, numbers from 1 to 10 12334566 and long Supercalifragilisticexpialidocious", 12, 2, 'long string,'],
            ["I'm a very long string, with words, numbers from 1 to 10 12334566 and long Supercalifragilisticexpialidocious", 109, 1, "I'm a very long string, with words, numbers from 1 to 10 12334566 and long Supercalifragilisticexpialidocious"],
            ['', 98, 1, ''],
            [null, 109, 1, ''],
            ["I'm a very long string, with words, numbers from 1 to 10 12334566 and long Supercalifragilisticexpialidocious", 10, 11, 'Supercalif'],
            ["I'm a very long string, with words, numbers from 1 to 10 12334566 and long Supercalifragilisticexpialidocious", 10, 12, 'ragilistic'],
            ["I'm a very long string, with words, numbers from 1 to 10 12334566 and long Supercalifragilisticexpialidocious", 10, 13, 'expialidoc'],
            ["I'm a very long string, with words, numbers from 1 to 10 12334566 and long Supercalifragilisticexpialidocious", 10, 14, 'ious'],
            ["I'm a very long string, with words, numbers from 1 to 10 12334566 and long Supercalifragilisticexpialidocious", 1023, 1, "I'm a very long string, with words, numbers from 1 to 10 12334566 and long Supercalifragilisticexpialidocious"],
            ['Supercalifragilisticexpialidocious', 1, 1, 'S'],
            ['Supercalifragilisticexpialidocious', 1, 34, 's'],
            ['Supercalifragilisticexpialidocious', 17, 1, 'Supercalifragilis'],
            ['Supercalifragilisticexpialidocious', 17, 2, 'ticexpialidocious'],
        ];
    }

    public function testFormatDateWhenDateIsNull()
    {
        $this->assertNull($this->mapper->formatDate('Y-m-d', null));
    }

    public function testFormatDate()
    {
        $dateTime = new \DateTime('2012-07-08 12:03:04');

        $date = $this->mapper->formatDate('Y-m-d', $dateTime);

        $this->assertSame('2012-07-08', $date);
    }

    public function testFormatDateTimeUtc()
    {
        $dateTime = new \DateTime('2016-04-20T20:00:00+0200');

        $date = $this->mapper->formatDateTimeUtc($dateTime);

        $this->assertSame('2016-04-20T18:00:00Z', $date);
    }

    public function testGetFirstElementArray()
    {
        $fruits = [
            'orange',
            'apple',
            'banana',
        ];

        $this->assertSame('orange', $this->mapper->first($fruits));
    }

    public function testConvertStringToDate()
    {
        $date = '2012-07-08 12:03:04';

        $this->assertInstanceOf(\DateTime::class, $this->mapper->stringToDate($date));
    }

    public function testConvertStringToDateWhenDateIsNull()
    {
        $formattedDate = $this->mapper->stringToDate(null);

        $this->assertNull($formattedDate);
    }

    public function testToSoapVarObj()
    {
        $soapVar = $this->mapper->toSoapVarObj(['a' => 'b'], SOAP_ENC_OBJECT, 'Account');

        $this->assertInstanceOf(\SoapVar::class, $soapVar);
        $this->assertEquals(SOAP_ENC_OBJECT, $soapVar->enc_type);
        $this->assertEquals(['a' => 'b'], $soapVar->enc_value);
        $this->assertEquals('Account', $soapVar->enc_stype);
    }

    public function testTransformToSoapVar()
    {
        $data = [
            'boolean_test' => true,
            'string_test' => 'some_string',
            'array_test'=>[
                'string_test_2'=> 'some_other_string'
            ]
        ];
        $data = $this->mapper->arrayToSoapVars($data);

        $this->assertNotInstanceOf(\SoapVar::class, $data['boolean_test'], 'Failed to assert that $data[\'boolean_test\'] is not a \SoapVar.');
        $this->assertNotInstanceOf(\SoapVar::class, $data["array_test"],'Failed to assert that $data["array_test"] is not a \SoapVar.');

        $this->assertFalse(is_string($data['string_test']), 'Failed to assert that our test_string is no longer a string. Should have become a \SoapVar.');
        $this->assertFalse(is_string($data['array_test']['string_test_2']),'Failed to assert that "test_string => string_test_2" is no longer a string. Should have become a \SoapVar.');

        $this->assertTrue($data['boolean_test']);
        $this->assertInstanceOf(\SoapVar::class, $data['string_test'], 'Failed to assert that $data[\'string_test\'] was converted to a \SoapVar.');
        $this->assertInstanceOf(\SoapVar::class, $data['array_test']['string_test_2'],'Failed to assert that $data[\'array_test\'][\'string_test_2\'] was converted to a \SoapVar.');

        $this->assertEquals('some_string', $data['string_test']->enc_value, 'Failed to assert that the content(enc_value) of the \SoapVar for $data[\'string_test\'] is equal to \'some_string\'.');
        $this->assertEquals('some_other_string', $data['array_test']['string_test_2']->enc_value,'Failed to assert that the content(enc_value) of the \SoapVar for $data[\'array_test\'][\'string_test_2\'] is equal to \'some_string\'.');
    }

    public function testConvertListToString()
    {
        $list = [
            'a',
            'b',
        ];

        $this->assertSame('a;b', $this->mapper->arrayToString(';', $list));
    }

    public function testFlattenArrayByKey()
    {
        $array = [
            ['fruits' => 'banana'],
            ['fruits' => 'apple'],
        ];

        $res = $this->mapper->flattenArrayByKey($array, 'fruits');
        $this->assertSame(['banana', 'apple'], $res);

        $res = $this->mapper->flattenArrayByKey(['car'], 'fruits');
        $this->assertEmpty($res);
    }

    public function testKeyExists()
    {
        $array = [
            'fruits' => 'banana',
        ];

        $this->assertTrue($this->mapper->keyExists($array, 'fruits'));
        $this->assertFalse($this->mapper->keyExists($array, 'vegetables'));
    }

    public function testStrReplace()
    {
        $string = 'banana';
        $needle = 'a';
        $replacedBy = 'o';

        $this->assertEquals('bonono', $this->mapper->replaceStr($needle, $replacedBy, $string));
    }

    /**
     * @param $string
     * @param $length
     * @param $section
     * @param $expected
     *
     * @dataProvider wordWrapDataProvider
     */
    public function testWordWrap($string, $length, $section, $expected)
    {
        $result = $this->mapper->wordWrap($string, $length, $section);
        $this->assertEquals($expected, $result);
    }

    public function testEmptyTo()
    {
        $value = 5;
        // Testing empty values, returning a non null value
        $res = $this->mapper->emptyTo('', $value);
        $this->assertSame($value, $res);
        $res = $this->mapper->emptyTo(0, $value);
        $this->assertSame($value, $res);
        $res = $this->mapper->emptyTo(null, $value);
        $this->assertSame($value, $res);
        $res = $this->mapper->emptyTo(false, $value);
        $this->assertSame($value, $res);

        // Testing empty values, returning a null value
        $value = null;
        $res = $this->mapper->emptyTo('', $value);
        $this->assertSame($value, $res);
        $res = $this->mapper->emptyTo(0, $value);
        $this->assertSame($value, $res);
        $res = $this->mapper->emptyTo(null, $value);
        $this->assertSame($value, $res);
        $res = $this->mapper->emptyTo(false, $value);
        $this->assertSame($value, $res);

        $value = 5;
        // Testing non empty values, returning a non null value
        $input = 'A';
        $res = $this->mapper->emptyTo($input, $value);
        $this->assertSame($input, $res);
        $input = 10;
        $res = $this->mapper->emptyTo($input, $value);
        $this->assertSame($input, $res);
        $input = array(0 => 'A');
        $res = $this->mapper->emptyTo($input, $value);
        $this->assertSame($input, $res);

        $value = null;
        // Testing non empty values, returning a null value
        $input = 'A';
        $res = $this->mapper->emptyTo($input, $value);
        $this->assertSame($input, $res);
        $input = 10;
        $res = $this->mapper->emptyTo($input, $value);
        $this->assertSame($input, $res);
        $input = array(0 => 'A');
        $res = $this->mapper->emptyTo($input, $value);
        $this->assertSame($input, $res);
    }

    /**
     * @param mixed $expected
     * @param mixed $obj
     * @param array $context
     *
     * @dataProvider provideEmptyValues
     */
    public function testShouldAcceptEmptyValues($expected, $obj, $context = [])
    {
        $this->mapper->addMappings(['foo' => 'My Awesome mapping']);
        /** @var ExpressionEvaluator|\PHPUnit_Framework_MockObject_MockObject $evaluator */
        $evaluator = $this->createMock(ExpressionEvaluator::class);
        $evaluator->expects($this->any())
            ->method('evaluateWithVars')
            ->willReturnCallback(function ($mapping, $dictionary) {
                return ['name' => $dictionary['obj']];
            });
        $this->mapper->setEvaluator($evaluator);

        $this->assertSame($expected, $this->mapper->map($obj, 'foo', $context));
    }

    public function provideEmptyValues()
    {
        yield 'No option' => ['', ''];

        $ctx = ['allow_empty_string' => false];
        yield 'Empty forbidden option' => ['', '', $ctx];

        $ctx['allow_empty_string'] = true;

        yield 'Empty allowed option' => [['name' => ''], '', $ctx];
        yield 'Wrong type' => [null, null, $ctx];
    }

    /**
     * @dataProvider provideMergeMappings
     */
    public function testShouldMergeMappings($elements, $mappers, $mockedMapFunction, $mockedMapAllFunction, $expectedResult)
    {
        $mapperMock = $this->getMockBuilder(Mapper::class)
            ->setMethods(['mapAll', 'map'])
            ->getMock();

        $mapperMock->method('map')->willReturn($mockedMapFunction);
        $mapperMock->method('mapAll')->willReturn($mockedMapAllFunction);

        $result = $mapperMock->mergeMapping($elements, 'mappingName', $mappers);

        $this->assertSame($expectedResult, $result);
    }

    public function provideMergeMappings()
    {
        yield 'Merge mappings' => [
            [
                'packshot' => 'url://packshot.com',
                'lengow' => null,
                'eRetail' => [
                    'main' => 'url://main.com',
                    'nonBrand' => [
                        'url://nonBrand1.com',
                        'url://nonBrand2.com'
                    ]
                ]
            ],
            [
                ['map', 'packshot'],
                ['map', 'lengow'],
                ['map', 'maps'],
                ['map', 'eRetail.main'],
                ['mapAll', 'eRetail.nonBrand'],
            ],
            ['name' => 'singleLine', 'location' => 'https://media.com/1'],
            [['name' => 'multipleLines', 'location' => 'https://media.com/2'],['name' => 'multipleLines', 'location' => 'https://media.com/3']],
            [
                ['name' => 'singleLine', 'location' => 'https://media.com/1'],
                ['name' => 'singleLine', 'location' => 'https://media.com/1'],
                ['name' => 'multipleLines', 'location' => 'https://media.com/2'],
                ['name' => 'multipleLines', 'location' => 'https://media.com/3']
            ],
        ];
        yield 'Merge mappings with null mapAll' => [
            [
                'line' => 'https://media.com/1',
                'lines' => null
            ],
            [
                ['map', 'line'],
                ['mapAll', 'lines'],
            ],
            ['name' => 'singleLine', 'location' => 'https://media.com/1'],
            null,
            [
                ['name' => 'singleLine', 'location' => 'https://media.com/1']
            ],
        ];
        yield 'Merge mappings with null map' => [
            [
                'line' => null,
                'lines' => [
                    'https://media.com/2',
                    'https://media.com/3'
                ]
            ],
            [
                ['map', 'line'],
                ['mapAll', 'lines'],
            ],
            null,
            [['name' => 'multipleLines', 'location' => 'https://media.com/2'],['name' => 'multipleLines', 'location' => 'https://media.com/3']],
            [
                ['name' => 'multipleLines', 'location' => 'https://media.com/2'],
                ['name' => 'multipleLines', 'location' => 'https://media.com/3']
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidMappersToMerge
     */
    public function testShouldThrowExceptionIfMappersIsNotCorrect(array $mapper = null)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->mapper->mergeMapping([
            'line' => 'https://media.com/1',
            'lines' => ['https://media.com/2','https://media.com/3']
        ], 'test', $mapper);
    }

    public function provideInvalidMappersToMerge()
    {
        yield 'Null mappers parameter' => [null];
        yield 'Map function is not mapped' => [[['invalid', 'line']]];
        yield 'Map function is null' => [[[null, 'line']]];
    }
}
