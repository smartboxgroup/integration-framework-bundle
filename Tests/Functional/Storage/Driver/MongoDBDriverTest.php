<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Storage\Driver;

use JMS\Serializer\SerializerInterface;
use MongoDB\BSON\ObjectID;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\MongoDB\MongoDBDriver;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\NoSQLDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\QueryOptions;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Exceptions\NoSQLDriverException;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Events\FakeEvent;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Serializables\Entity\SerializableSimpleEntity;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MongoDBDriverTest.
 *
 * @coversDefaultClass \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\MongoDB\MongoDBDriver
 */
class MongoDBDriverTest extends KernelTestCase
{
    const MONGO_DATABASE = 'tests';
    const MONGO_COLLECTION = 'tests_collection';

    /** @var ContainerInterface */
    protected static $container;

    /** @var SerializerInterface */
    protected static $serializer;

    /** @var NoSQLDriverInterface */
    protected static $storageDriver;

    public static function setUpBeforeClass(): void
    {
        if (!\extension_loaded('mongodb')) {
            self::markTestSkipped(
                'The MongoDB extension is not available.'
            );

            return;
        }

        $kernel = self::createKernel();
        $kernel->boot();

        self::$container = $kernel->getContainer();
        self::$serializer = $kernel->getContainer()->get('jms_serializer');

        self::$storageDriver = new MongoDBDriver();
        self::$storageDriver->configure(['host' => 'mongodb://localhost:27017', 'database' => self::MONGO_DATABASE]);

        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass(): void
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
     *
     * @param array $configuration
     *
     * @covers ::configure
     * @covers ::save
     */
    public function testConfigureForCorrectConfiguration(array $configuration)
    {
        $this->markTestSkipped('To be reviewed before reinstating..');
        $storageDriver = new MongoDBDriver();
        $storageDriver->configure($configuration);

        $data = new SerializableSimpleEntity();
        $data->setTitle('some title');
        $data->setDescription('some description');
        $data->setNote('some note');

        $data = self::$serializer->serialize($data, 'array');
        $this->assertNotNull($storageDriver->insertOne(self::MONGO_COLLECTION, $data));

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
     *
     * @param array $configuration
     *
     * @covers ::configure
     */
    public function testConfigureForIncorrectConfiguration(array $configuration)
    {
        $this->markTestSkipped('To be reviewed before reinstating..');
        $this->expectException(NoSQLDriverException::class);

        $storageDriver = new MongoDBDriver();
        $storageDriver->configure($configuration);

        unset($storageDriver);
    }

    /**
     * @return array
     */
    public function dataProviderForStorageDriver()
    {
        $dataSets = [];

        for ($i = 0; $i < 5; ++$i) {
            $object = new FakeEvent();
            $object->setTimestamp(new \DateTime());
            $object->setName('test_'.$i);
            $dataSets[] = [$object];
        }

        return $dataSets;
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
     * @param SerializableInterface $data
     *
     * @dataProvider dataProviderForStorageDriver
     *
     * @covers ::save
     * @covers ::findOneById
     */
    public function testSaveAndFind(SerializableInterface $data)
    {
        $this->markTestSkipped('To be reviewed before reinstating..');
        $data = self::$serializer->serialize($data, 'array');
        $id = self::$storageDriver->insertOne(self::MONGO_COLLECTION, $data);

        $queryOptions = new QueryOptions();
        $queryOptions->setQueryParams([
            '_id' => new ObjectID((string) $id),
        ]);

        $restoredData = self::$storageDriver->find(self::MONGO_COLLECTION, $queryOptions);

        $restoredData = \reset($restoredData);
        unset($restoredData['_id']);
        $this->assertEquals($data, $restoredData);
    }

    /**
     * @param string $id
     *
     * @dataProvider dataProviderForNotExistingData
     *
     * @covers ::findOneById
     */
    public function testFindForNotExistingData($id)
    {
        $this->markTestSkipped('To be reviewed before reinstating..');
        try {
            $id = new ObjectID((string) $id);
        } catch (\Exception $e) {
            return;
        }

        $queryOptions = new QueryOptions();
        $queryOptions->setQueryParams([
            '_id' => $id,
        ]);

        $restoredData = self::$storageDriver->find(self::MONGO_COLLECTION, $queryOptions);

        $this->assertEquals(0, \count($restoredData));
    }

    /**
     * @param SerializableInterface $data
     *
     * @dataProvider dataProviderForStorageDriver
     *
     * @covers ::findOneById
     */
    public function testUpdate(SerializableInterface $data)
    {
        $this->markTestSkipped('To be reviewed before reinstating..');
        $data = self::$serializer->serialize($data, 'array');
        $id = self::$storageDriver->insertOne(self::MONGO_COLLECTION, $data);

        $queryOptions = new QueryOptions();
        $queryOptions->setQueryParams([
            '_id' => new ObjectID((string) $id),
        ]);
        $updateName = 'UpdatedName';
        $transformation = array('$set' => array('name' => $updateName));

        self::$storageDriver->update(self::MONGO_COLLECTION, $queryOptions, $transformation);

        $restoredData = self::$storageDriver->find(self::MONGO_COLLECTION, $queryOptions);

        $data['name'] = $updateName;

        $restoredData = \reset($restoredData);
        unset($restoredData['_id']);
        $this->assertEquals($data, $restoredData);
    }
}
