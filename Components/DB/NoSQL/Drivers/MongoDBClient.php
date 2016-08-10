<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers;

use JMS\Serializer\SerializerInterface;
use MongoDB\BSON\ObjectID;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Components\DB\Storage\Exception\DataStorageException;
use Smartbox\Integration\FrameworkBundle\Components\DB\Storage\Exception\StorageException;
use Smartbox\Integration\FrameworkBundle\Components\DB\Storage\Query\QueryOptions;
use Smartbox\Integration\FrameworkBundle\Components\DB\Storage\Query\QueryOptionsInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\Storage\StorageClientInterface;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class MongoDBClient.
 */
class MongoDBClient implements StorageClientInterface, SerializableInterface
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

    /**
     * {@inheritdoc}
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
            throw new StorageException('Wrong configuration: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (!isset($this->configuration['host']) || !isset($this->configuration['database'])) {
            throw new StorageException('Can not connect to MongoDB because configuration for this driver was not provided.');
        }

        try {
            $this->connection = new \MongoDB\Client(
                $this->configuration['host'],
                $this->configuration['options'],
                $this->configuration['driver_options']
            );
        } catch (\Exception $e) {
            throw new StorageException('Can not connect to storage because of: '.$e->getMessage(), $e->getCode(), $e);
        }

        $this->db = $this->connection->selectDatabase($this->configuration['database']);

        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
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
    public function save($collection, SerializableInterface $storageData)
    {
        $this->ensureConnection();

        try {
            $data = $this->serializer->serialize($storageData, 'mongo_array');

            /** @var \MongoDB\InsertOneResult $insertOneResult */
            $insertOneResult = $this->db->$collection->insertOne($data);
        } catch (\Exception $e) {
            $exception = new DataStorageException('Can not save data to storage: '.$e->getMessage(), $e->getCode(), $e);
            $exception->setStorageData($storageData);

            throw $exception;
        }

        return (string) $insertOneResult->getInsertedId();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($collection, QueryOptionsInterface $queryOptions)
    {
        $this->ensureConnection();
        $this->db->$collection->deleteMany($queryOptions->getQueryParams());
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @TODO $hydrateObject argument should be removed as it's not in an interface
     */
    public function findOne($collection, QueryOptionsInterface $queryOptions, array $fields = [], $hydrateObject = true)
    {
        $this->ensureConnection();

        try {
            $result = $this->db->$collection->findOne($queryOptions->getQueryParams(), $fields);
        } catch (\Exception $e) {
            throw new StorageException('Can not retrieve data from storage: '.$e->getMessage(), $e->getCode(), $e);
        }

        $result = (array) $result;
        if (!empty($result) && $hydrateObject) {
            unset($result['_id']);

            return $this->hydrateResult($result);
        }

        return;
    }

    /**
     * {@inheritdoc}
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
            throw new StorageException('Can not retrieve data from storage: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
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
            throw new StorageException('Can not retrieve data from storage: '.$e->getMessage(), $e->getCode(), $e);
        }

        return $count;
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

    /**
     * Executes an aggregation pipeline on a given collection.
     *
     * @param string $collection
     * @param array  $pipeline
     * @param array  $options
     *
     * @return array
     */
    public function aggregate($collection, array $pipeline, array $options = [])
    {
        $this->ensureConnection();

        try {
            $data = $this->db->$collection->aggregate($pipeline, $options);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot aggregate on collection "%s": %s (Code: %d)',
                    $collection,
                    $e->getMessage(),
                    $e->getCode()
                )
            );
        }

        $result = [];

        foreach ($data as $item) {
            $result[] = (array) $item;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
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
}
