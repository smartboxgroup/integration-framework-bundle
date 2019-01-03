<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Tools\Mapper;

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
}
