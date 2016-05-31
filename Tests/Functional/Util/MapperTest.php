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

    public function setUp()
    {
        parent::setUp();
        $this->mapper = $this->getContainer()->get('smartesb.util.mapper');
    }

    public function dataProviderForCorrectMappings()
    {
        return [
            'Test mapping with simple expression' => [
                [
                    'x_to_xyz' => [
                        'x' => "obj.get('x') + 1",
                        'y' => "obj.get('x') + 2",
                        'z' => "obj.get('x') + 3",
                    ],
                ],
                'x_to_xyz',
                new SerializableArray(['x' => 10]),
                [
                    'x' => 11,
                    'y' => 12,
                    'z' => 13,
                ]
            ],
            'Test null values' => [
                [
                    'mapping_name' => [
                        'x' => "obj.get('x') + 1",
                        'y' => "obj.get('y')",
                    ],
                ],
                'mapping_name',
                new SerializableArray(['x' => 10]),
                [
                    'x' => 11,
                ]
            ],
            'Test nested data' => [
                [
                    'xyz_to_x' => [
                        'x' => "obj.get('x') + obj.get('y') + obj.get('z')",
                        'origins' => "mapper.mapAll([obj.get('x'),obj.get('y'),obj.get('z')],'single_value')",
                    ],
                    'single_value' => [
                        'value' => 'obj',
                    ],
                ],
                'xyz_to_x',
                new SerializableArray([
                    'x' => 10,
                    'y' => 5,
                    'z' => 1,
                ]),
                [
                    'x' => 16,
                    'origins' => [
                        ['value' => 10],
                        ['value' => 5],
                        ['value' => 1],
                    ],
                ]
            ],
            'Test DateTime objects' => [
                [
                    'mapping_name' => [
                        'date_0' => "mapper.formatDate('Y-m-d', obj.get('null_value'))",
                        'date_1' => "mapper.formatDate('Y-m-d H:i:s', obj.get('date'))",
                        'date_2' => "mapper.formatDate(ISO8601, obj.get('date'))",
                        'date_3' => "mapper.formatDate(ISO8601Micro, obj.get('date'))",
                    ],
                ],
                'mapping_name',
                new SerializableArray([
                    'date' => \DateTime::createFromFormat(\DateTime::ISO8601, '2015-01-01T20:00:00+01:00'),
                    'null_value' => null,
                ]),
                [
                    'date_1' => '2015-01-01 20:00:00',
                    'date_2' => '2015-01-01T20:00:00+0100',
                    'date_3' => '2015-01-01T20:00:00.000',
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForCorrectMappings
     * @param array $mappings
     * @param $mappingName
     * @param $mappedValue
     * @param array $expectedValue
     */
    public function testMap(array $mappings, $mappingName, $mappedValue, array $expectedValue)
    {
        $this->mapper->addMappings($mappings);

        $res = $this->mapper->map($mappedValue, $mappingName);

        $this->assertEquals($expectedValue, $res);
    }
}
