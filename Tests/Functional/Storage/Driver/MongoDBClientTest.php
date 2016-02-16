<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Storage\Driver;

use JMS\Serializer\SerializerInterface;
use Smartbox\CoreBundle\Type\Date;
use Smartbox\CoreBundle\Type\Integer;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Storage\Driver\MongoDBClient;
use Smartbox\Integration\FrameworkBundle\Storage\Exception\StorageException;
use Smartbox\Integration\FrameworkBundle\Storage\Filter\StorageFilter;
use Smartbox\Integration\FrameworkBundle\Storage\StorageClientInterface;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Events\FakeEvent;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Serializables\Entity\SerializableSimpleEntity;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Serializables\SimpleObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MongoDBClientTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Functional\Storage\Driver
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Storage\Driver\MongoDBClient
 */
class MongoDBClientTest extends KernelTestCase
{
    const MONGO_DATABASE = 'tests';
    const MONGO_COLLECTION = 'tests_collection';

    /** @var ContainerInterface */
    protected static $container;

    /** @var SerializerInterface */
    protected static $serializer;

    /** @var StorageClientInterface */
    protected static $storageDriver;

    public static function setUpBeforeClass()
    {
        $kernel = self::createKernel();
        $kernel->boot();

        self::$container = $kernel->getContainer();

        self::$serializer = self::$container->get('serializer');
        self::$storageDriver = new MongoDBClient(self::$serializer);
        self::$storageDriver->configure(['host' => 'mongodb://localhost:27017', 'database' => self::MONGO_DATABASE]);

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
            [['host' => 'mongodb://localhost', 'database' => 'test_database']],
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
        $storageDriver = new MongoDBClient(self::$serializer);
        $storageDriver->configure($configuration);

        $data = new SerializableSimpleEntity();
        $data->setTitle('some title');
        $data->setDescription('some description');
        $data->setNote('some note');

        $this->assertNotNull($storageDriver->save(self::MONGO_COLLECTION, $data));

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
            [['database' => 'test_database']],
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

        $storageDriver = new MongoDBClient(self::$serializer);
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
            $object = new FakeEvent();
            $object->setTimestamp(new \DateTime());
            $object->setName('test_' . $i);
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
     * @covers ::findOneById
     */
    public function testSaveAndFindOneById(SerializableInterface $data)
    {
        $id = self::$storageDriver->save(self::MONGO_COLLECTION, $data);

        $restoredData = self::$storageDriver->findOneById(self::MONGO_COLLECTION, $id);

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
            [000000000000],
        ];
    }

    /**
     * @param string $id
     *
     * @dataProvider dataProviderForNotExistingData
     *
     * @covers ::findOneById
     */
    public function testFindOneByIdForNotExistingData($id)
    {
        $restoredData = self::$storageDriver->findOneById(self::MONGO_COLLECTION, $id);

        $this->assertNull($restoredData);
    }
}
