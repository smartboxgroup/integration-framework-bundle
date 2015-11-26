<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Util;

use JMS\Serializer\SerializerInterface;
use Smartbox\Integration\FrameworkBundle\Tests\App\AppKernel;
use Smartbox\Integration\FrameworkBundle\Tests\EntityX;
use Smartbox\Integration\FrameworkBundle\Util\JMSSerializerFormatter;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class JMSSerializerFormatterTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Util
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Util\JMSSerializerFormatter
 */
class JMSSerializerFormatterTest extends WebTestCase
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    public function setUp()
    {
        self::$class = null;
        static::bootKernel();
        $container = static::$kernel->getContainer();
        $this->serializer = $container->get('serializer');
    }

    public function tearDown(){
        parent::tearDown();
        self::$class = null;
    }

    public static function getKernelClass(){
        return AppKernel::class;
    }

    public function dataProviderForFormatter()
    {
        return [
            [
                '[{"x":10}]',
                new EntityX(10)
            ],
            [
                '[{"x":15}]',
                new EntityX(15)
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForFormatter
     * @covers ::format
     * @covers  Smartbox\Integration\PlatformBundle\Logging\SerializationListener::onPostSerialize
     *
     * @param $expected
     * @param $entity
     * @return string
     */
    public function testFormat($expected, EntityX $entity)
    {
        $formatter = new JMSSerializerFormatter();
        $formatter->setSerializer($this->serializer);

        $this->assertEquals($expected, $formatter->format([$entity]));
    }
}