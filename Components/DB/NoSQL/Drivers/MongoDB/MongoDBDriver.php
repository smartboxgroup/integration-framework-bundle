<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\MongoDB;

use JMS\Serializer\SerializerInterface;
use MongoDB\BSON\ObjectID;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\NoSQLDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\QueryOptions;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\QueryOptionsInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Exceptions\NoSQLDriverDataException;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Exceptions\NoSQLDriverException;
use Smartbox\Integration\FrameworkBundle\Service;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class MongoDBDriver.
 */
class MongoDBDriver extends Service implements NoSQLDriverInterface, SerializableInterface
{
    use HasInternalType;

    /** @var SerializerInterface */
    protected $serializer;

    /** @var array */
    protected $configuration;

    /** @var \MongoDB\Client */
    protected $connection;

    /** @var \MongoDB\Database */
    protected $db;

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Define and apply the expected configuration for the driver
     *
     * @param array $configuration
     *
     * @throws \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Exceptions\NoSQLDriverException
     */
    public function configure(array $configuration)
    {
        // allows to use the default options when a null value is passed
        if (array_key_exists('options', $configuration) && $configuration['options'] === null) {
            unset($configuration['options']);
        }

        $optionsResolver = new OptionsResolver();
        $optionsResolver
            ->setDefined(['host', 'database', 'options', 'driver_options'])
            ->setDefaults([
                'options' => ['connect' => false],
                'driver_options' => ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']],
            ])
            ->setRequired(['host', 'database'])
            ->setAllowedTypes('host', 'string')
            ->setAllowedTypes('database', 'string')
            ->setAllowedTypes('options', 'array')
            ->setAllowedTypes('driver_options', 'array')
        ;

        try {
            $this->configuration = $optionsResolver->resolve($configuration);
        } catch (\Exception $e) {
            throw new NoSQLDriverException('Wrong configuration: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Connect to the mongoDatabase
     *
     * @return \MongoDB\Client
     *
     * @throws \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Exceptions\NoSQLDriverException
     */
    public function connect()
    {
        if (!isset($this->configuration['host']) || !isset($this->configuration['database'])) {
            throw new NoSQLDriverException('Can not connect to MongoDB because configuration for this driver was not provided.');
        }

        try {
            $this->connection = new \MongoDB\Client(
                $this->configuration['host'],
                $this->configuration['options'],
                $this->configuration['driver_options']
            );
        } catch (\Exception $e) {
            throw new NoSQLDriverException('Can not connect to storage because of: '.$e->getMessage(), $e->getCode(), $e);
        }

        $this->db = $this->connection->selectDatabase($this->configuration['database']);

        return $this->connection;
    }

    public function disconnect()
    {
        if ($this->connection instanceof \MongoDB\Client && $this->connection->connected) {
            $this->connection = null;
        }
    }

    protected function ensureConnection()
    {
        if (!$this->connection instanceof \MongoDB\Client || !$this->connection->connected) {
            $this->connect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function insertOne($collection, SerializableInterface $storageData)
    {
        $this->ensureConnection();

        try {
            $data = $this->serializer->serialize($storageData, 'mongo_array');

            /** @var \MongoDB\InsertOneResult $insertOneResult */
            $insertOneResult = $this->db->$collection->insertOne($data);
        } catch (\Exception $e) {
            $exception = new NoSQLDriverDataException('Can not save data to storage: '.$e->getMessage(), $e->getCode(), $e);
            $exception->setStorageData($storageData);

            throw $exception;
        }

        return (string) $insertOneResult->getInsertedId();
    }

    /**
     * {@inheritdoc}
     */
    public function insertMany($collection, array $data)
    {
        $this->ensureConnection();

        try {
            $data = $this->serializer->serialize($data, 'mongo_array');

            /** @var \MongoDB\InsertOneResult $insertOneResult */
            $insertOneResult = $this->db->$collection->insertMany($data);
        } catch (\Exception $e) {
            $exception = new NoSQLDriverDataException('Can not save data to storage: '.$e->getMessage(), $e->getCode(), $e);
            $exception->setStorageData(new SerializableArray($data));

            throw $exception;
        }

        return (string) $insertOneResult->getInsertedId();
    }

    /**
     * {@inheritdoc}
     */
    public function update($collection, QueryOptionsInterface $queryOptions, array $storageData)
    {
        $this->ensureConnection();

        try {
            $data = $this->serializer->serialize($storageData, 'mongo_array');
            /** @var \MongoDB\UpdateResult $updateResult */
            $updateResult = $this->db->$collection->updateMany($queryOptions->getQueryParams(), $data);
        } catch (\Exception $e) {
            $exception = new NoSQLDriverException('Can not update data with storage: '.$e->getMessage(), $e->getCode(), $e);
            throw $exception;
        }

        return $updateResult;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($collection, QueryOptionsInterface $queryOptions)
    {
        $this->ensureConnection();

        try {
            /** @var \MongoDB\DeleteResult $deleteResult */
            $deleteResult = $this->db->$collection->deleteMany($queryOptions->getQueryParams());
        } catch (\Exception $e) {
            $exception = new NoSQLDriverException('Can not update data to storage: '.$e->getMessage(), $e->getCode(), $e);
            throw $exception;
        }

        return $deleteResult;
    }

    /**
     * {@inheritdoc}
     */
    public function find($collection, QueryOptionsInterface $queryOptions, array $fields = [], $hydrateObject = true)
    {
        $cursor = $this->findWithCursor($collection, $queryOptions, $fields);

        $result = [];

        foreach ($cursor as $item) {
            $item = (array) $item;
            $id = $item['_id'];

            unset($item['_id']);

            $result[(string) $id] = $item;

            if ($hydrateObject && isset($item['_type'])) {
                $result[(string) $id] = $this->hydrateResult($item);
            }
        }

        return $result;
    }

    /**
     * @param $collection
     * @param \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\QueryOptionsInterface $queryOptions
     * @param array $fields
     *
     * @return \MongoDB\Driver\Cursor
     * @throws \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Exceptions\NoSQLDriverException
     */
    public function findWithCursor($collection, QueryOptionsInterface $queryOptions, array $fields = [])
    {
        $this->ensureConnection();

        $queryParams = $queryOptions->getQueryParams();
        $options = [
            'sort' => $queryOptions->getSortParams(),
            'limit' => $queryOptions->getLimit(),
            'skip' => $queryOptions->getOffset(),
        ];

        if (!empty($fields)) {
            $options['projection'] = $fields;
        }

        try {
            $cursor = $this->db->$collection
                ->find($queryParams, $options)
            ;

            return $cursor;
        } catch (\Exception $e) {
            throw new NoSQLDriverException('Can not retrieve data from storage: '.$e->getMessage(), $e->getCode(), $e);
        }
    }


    /**
     * Hydrates a resulting array object from a query.
     *
     * @param array $result
     *
     * @return SerializableInterface[]|SerializableInterface
     */
    public function hydrateResult(array $result)
    {
         return $this->serializer->deserialize($result, SerializableInterface::class, 'mongo_array');
    }

    public function doDestroy()
    {
        $this->disconnect();
    }

    /**
     * Calls the doDestroy method on kernel.terminate event.
     *
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this->doDestroy();
    }

    /**
     * Calls the doDestroy method on console.terminate event.
     *
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $this->doDestroy();
    }

    /**
     * Remove an entry of the mongo database
     *
     * @param $collection
     * @param $id
     */
    public function deleteById($collection, $id)
    {
        try {
            $id = new ObjectID((string) $id);
        } catch (\Exception $e) {
            return;
        }

        $queryOptions = new QueryOptions();
        $queryOptions->setQueryParams([
            '_id' => $id,
        ]);

        $this->ensureConnection();
        $this->db->$collection->deleteOne($queryOptions->getQueryParams());
    }

    /**
     * @param $collection
     * @param \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\QueryOptionsInterface $queryOptions
     * @param array $fields
     * @param bool $hydrateObject
     *
     * @return SerializableInterface|\Smartbox\CoreBundle\Type\SerializableInterface[]|void
     * @throws \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Exceptions\NoSQLDriverException
     */
    public function findOne($collection, QueryOptionsInterface $queryOptions, array $fields = [], $hydrateObject = true)
    {
        $this->ensureConnection();

        try {
            $result = $this->db->$collection->findOne($queryOptions->getQueryParams(), $fields);
        } catch (\Exception $e) {
            throw new NoSQLDriverException('Can not retrieve data from storage: '.$e->getMessage(), $e->getCode(), $e);
        }

        $result = (array) $result;
        if (!empty($result) && $hydrateObject) {
            unset($result['_id']);

            return $this->hydrateResult($result);
        }

        return;
    }

    /**
     * @param $collection
     * @param $id
     * @param array $fields
     * @param bool $hydrateObject
     *
     * @return SerializableInterface|\Smartbox\CoreBundle\Type\SerializableInterface[]|void
     */
    public function findOneById($collection, $id, array $fields = [], $hydrateObject = true)
    {
        try {
            $id = new ObjectID((string) $id);
        } catch (\Exception $e) {
            return;
        }

        $queryOptions = new QueryOptions();
        $queryOptions->setQueryParams([
            '_id' => $id,
        ]);

        return $this->findOne($collection, $queryOptions, $fields, $hydrateObject);
    }


    /**
     * @param string $collection
     * @param QueryOptionsInterface $queryOptions
     *
     * @return int
     * @throws \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Exceptions\NoSQLDriverException
     */
    public function count($collection, QueryOptionsInterface $queryOptions)
    {
        $this->ensureConnection();

        $queryParams = $queryOptions->getQueryParams();

        try {
            /** @var \MongoDB\Collection $collection */
            $collection = $this->db->$collection;
            $count = $collection->count($queryParams);
        } catch (\Exception $e) {
            throw new NoSQLDriverException('Can not retrieve data from storage: '.$e->getMessage(), $e->getCode(), $e);
        }

        return $count;
    }
}
