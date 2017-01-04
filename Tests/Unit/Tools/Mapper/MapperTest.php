<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Tools\Mapper;

use Smartbox\Integration\FrameworkBundle\Tools\Mapper\Mapper;

class MapperTest extends \PHPUnit_Framework_TestCase
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
            ['fruits' => 'apple']
        ];

        $res = $this->mapper->flattenArrayByKey($array, 'fruits');
        $this->assertSame(['banana', 'apple'], $res);

        $res = $this->mapper->flattenArrayByKey(['car'], 'fruits');
        $this->assertEmpty($res);
    }

    public function testKeyExists()
    {
        $array = [
            'fruits' => 'banana'
        ];

        $this->assertTrue($this->mapper->keyExists($array, 'fruits'));
        $this->assertFalse($this->mapper->keyExists($array, 'vegetables'));

    }

}
