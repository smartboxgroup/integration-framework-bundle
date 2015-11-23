<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Storage\Driver;

use JMS\Serializer\SerializerInterface;
use Smartbox\CoreBundle\Type\Integer;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Storage\Driver\MongoDBStorage;
use Smartbox\Integration\FrameworkBundle\Storage\Exception\StorageException;
use Smartbox\Integration\FrameworkBundle\Storage\StorageInterface;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Serializables\Entity\SerializableSimpleEntity;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Serializables\SimpleObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MongoDBStorageTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Functional\Storage\Driver
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Storage\Driver\MongoDBStorage
 */
class MongoDBStorageTest extends KernelTestCase
{
    /** @var ContainerInterface */
    protected static $container;

    /** @var SerializerInterface */
    protected static $serializer;

    /** @var StorageInterface */
    protected static $storageDriver;

    public static function setUpBeforeClass()
    {
        $kernel = self::createKernel();
        $kernel->boot();

        self::$container = $kernel->getContainer();

        self::$serializer = self::$container->get('serializer');
        self::$storageDriver = new MongoDBStorage(self::$serializer);
        self::$storageDriver->configure(['database' => 'tests']);

        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass()
    {
        self::$storageDriver = null;
        parent::tearDownAfterClass();
    }

    /**
     * @return array
     */
    public function dataProviderForStorageDriverCorrectConfiguration()
    {
        return [
            [['host' => 'mongodb://localhost:27017', 'database' => 'test_database']],
            [['host' => 'localhost', 'database' => 'test_database']],
            [['database' => 'test1_database']],
            [['database' => 'test2_database']],
        ];
    }

    /**
     * @dataProvider dataProviderForStorageDriverCorrectConfiguration
     * @param array $configuration
     *
     * @covers ::configure
     * @covers ::save
     */
    public function testConfigureForCorrectConfiguration(array $configuration)
    {
        $storageDriver = new MongoDBStorage(self::$serializer);
        $storageDriver->configure($configuration);

        $data = new SerializableSimpleEntity();
        $data->setTitle('some title');
        $data->setDescription('some description');
        $data->setNote('some note');

        $this->assertNotNull($storageDriver->save('test_collection', $data));

        unset($storageDriver);
    }

    /**
     * @return array
     */
    public function dataProviderForStorageDriverIncorrectConfiguration()
    {
        return [
            [['unknown_host_key' => 'localhost']],
            [['unknown_database_key' => 'test1_database']],
            [['unknown_host_key' => 'host', 'unknown_database_key' => 'database']],
            [['host' => 'mongodb://localhost:27017']],
            [['host' => '---mongodb://localhost:27017', 'database' => 'test_database']],
            [['host' => 'unknown_host', 'database' => 'test_database']],
        ];
    }

    /**
     * @dataProvider dataProviderForStorageDriverIncorrectConfiguration
     * @param array $configuration
     *
     * @covers ::configure
     */
    public function testConfigureForIncorrectConfiguration(array $configuration)
    {
        $this->setExpectedException(StorageException::class);

        $storageDriver = new MongoDBStorage(self::$serializer);
        $storageDriver->configure($configuration);

        unset($storageDriver);
    }

    /**
     * @return array
     */
    public function dataProviderForStorageDriver()
    {
        $dataSets = [];
        for ($i = 0; $i < 5; $i++) {
            $object = new SimpleObject();
            $object->setIntegerValue($i);
            $object->setDoubleValue($i / 100);
            $object->setArrayOfIntegers([new Integer(1), new Integer(2)]);
            $dataSets[] = [$object];
        }

        return $dataSets;
    }

    /**
     * @param SerializableInterface $data
     *
     * @dataProvider dataProviderForStorageDriver
     *
     * @covers ::save
     * @covers ::findOne
     */
    public function testSaveAndFindOne(SerializableInterface $data)
    {
        $id = self::$storageDriver->save('test_collection', $data);

        $restoredData = self::$storageDriver->findOne('test_collection', $id);

        $this->assertEquals($data, $restoredData);
    }

    public function dataProviderForNotExistingData()
    {
        return [
            ['000000000000000000000000'],
            ['---'],
            ['not_existing_id'],
            [''],
            [null],
            [123],
        ];
    }

    /**
     * @param string $id
     *
     * @dataProvider dataProviderForNotExistingData
     *
     * @covers ::findOne
     */
    public function testFindOneForNotExistingData($id)
    {
        $restoredData = self::$storageDriver->findOne('test_collection', $id);

        $this->assertNull($restoredData);
    }
}