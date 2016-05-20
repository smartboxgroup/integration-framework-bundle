<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Util;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\BaseTestCase;
use Smartbox\Integration\FrameworkBundle\Tools\Mapper\Mapper;
use Smartbox\Integration\FrameworkBundle\Tools\Mapper\MapperInterface;

class MapperTest extends BaseTestCase
{
    /** @var  Mapper|MapperInterface */
    protected $mapper;

    protected $exampleMappings = [
        'x_to_xyz' => [
            'x' => "obj.get('x') + 1",
            'y' => "obj.get('x') + 2",
            'z' => "obj.get('x') + 3",
        ],
        'xyz_to_x' => [
            'x' => "obj.get('x') + obj.get('y') + obj.get('z')",
            'origins' => "mapper.mapAll([obj.get('x'),obj.get('y'),obj.get('z')],'single_value')",
        ],
        'single_value' => [
            'value' => 'obj',
        ],
    ];

    public function setUp()
    {
        parent::setUp();
        $this->mapper = $this->getContainer()->get('smartesb.util.mapper');
    }

    public function testMap()
    {
        $this->mapper->addMappings($this->exampleMappings);

        $res = $this->mapper->map(new SerializableArray([
            'x' => 10,
        ]), 'x_to_xyz');

        $this->assertEquals([
            'x' => 11,
            'y' => 12,
            'z' => 13,
        ], $res);
    }

    public function testMapNested()
    {
        $this->mapper->addMappings($this->exampleMappings);

        $res = $this->mapper->map(new SerializableArray([
            'x' => 10,
            'y' => 5,
            'z' => 1,
        ]), 'xyz_to_x');

        $this->assertEquals([
            'x' => 16,
            'origins' => [
                ['value' => 10],
                ['value' => 5],
                ['value' => 1],
            ],
        ], $res);
    }

    public function testToSoapVarObj()
    {
        /** @var \SoapVar $soapVar */
        $soapVar = $this->mapper->toSoapVarObj(['a' => 'b'], SOAP_ENC_OBJECT, 'Account');

        $this->assertInstanceOf(\SoapVar::class, $soapVar);
        $this->assertEquals(SOAP_ENC_OBJECT, $soapVar->enc_type);
        $this->assertEquals(['a' => 'b'], $soapVar->enc_value);
        $this->assertEquals('Account', $soapVar->enc_stype);
    }
}
