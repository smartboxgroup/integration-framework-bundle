<?php

namespace Smartbox\Integration\FrameworkBundle\Storage\Driver;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Storage\Exception\DataStorageException;
use Smartbox\Integration\FrameworkBundle\Storage\Exception\StorageException;
use Smartbox\Integration\FrameworkBundle\Storage\Filter\StorageFilter;
use Smartbox\Integration\FrameworkBundle\Storage\Filter\StorageFilterInterface;
use Smartbox\Integration\FrameworkBundle\Storage\StorageClientInterface;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use JMS\Serializer\SerializerInterface;

/**
 * Class MongoDBClient
 * @package Smartbox\Integration\FrameworkBundle\Storage\Driver
 */
class MongoDBClient implements StorageClientInterface, SerializableInterface
{
    use HasInternalType;

    /** @var SerializerInterface */
    protected $serializer;

    /** @var array */
    protected $configuration;

    /** @var \MongoClient */
    protected $connection;

    /** @var \MongoDB */
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
                'driver_options' => []
            ])
            ->setRequired(['host', 'database'])
            ->setAllowedTypes('host', 'string')
            ->setAllowedTypes('database', 'string')
            ->setAllowedTypes('options', 'array')
            ->setAllowedTypes('driver_options', 'array');

        try {
            $this->configuration = $optionsResolver->resolve($configuration);
        } catch (\Exception $e) {
            throw new StorageException('Wrong configuration: ' . $e->getMessage(), $e->getCode(), $e);
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
            $this->connection = $this->createConnection();
            $this->connection->connect();
        } catch (\Exception $e) {
            throw new StorageException('Can not connect to storage because of: ' . $e->getMessage(), $e->getCode(), $e);
        }

        $this->db = $this->connection->selectDB($this->configuration['database']);
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->connection instanceof \MongoClient && $this->connection->connected) {
            $this->connection->close(true);
        }
    }

    protected function ensureConnection()
    {
        if (!$this->connection instanceof \MongoClient || !$this->connection->connected) {
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
            $this->db->$collection->insert($data);
        } catch (\Exception $e) {
            $exception = new DataStorageException('Can not save data to storage: ' . $e->getMessage(), $e->getCode(), $e);
            $exception->setStorageData($storageData);

            throw $exception;
        }

        return (string)$data['_id'];
    }

    /**
     * Delete all the objects matching a given $filter
     *
     * @param string                 $collection
     * @param StorageFilterInterface $filter
     *
     * @return array|bool
     */
    public function delete($collection, StorageFilterInterface $filter)
    {
        $this->ensureConnection();
        return $this->db->$collection->remove($filter->getQueryParams());
    }

    /**
     * Helper function to delete a single record given a mongo id
     *
     * @param string          $collection
     * @param string|\MongoId $id
     *
     * @return array|bool
     */
    public function deleteById($collection, $id)
    {
        if (is_string($id)) {
            $id = new \MongoId($id);
        }

        $filter = new StorageFilter();
        $filter->setQueryParams([
            '_id' => $id
        ]);

        return $this->delete($collection, $filter);
    }

    /**
     * {@inheritdoc}
     */
    public function findOne($collection, StorageFilterInterface $filter, $fields = [], $hydrateObject = true)
    {
        $this->ensureConnection();

        try {
            $result = $this->db->$collection->findOne($filter->getQueryParams(), $fields);
        } catch (\Exception $e) {
            throw new StorageException('Can not retrieve data from storage: ' . $e->getMessage(), $e->getCode(), $e);
        }

        if (!empty($result) && $hydrateObject) {
            unset($result['_id']);
            return $this->hydrateResult($result);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneById($collection, $id, $fields = [], $hydrateObject = true)
    {
        if (!\MongoId::isValid($id)) {
            return null;
        }

        $filter = new StorageFilter();
        $filter->setQueryParams([
            '_id' => new \MongoId($id)
        ]);

        return $this->findOne($collection, $filter, $fields, $hydrateObject);
    }

    /**
     * {@inheritdoc}
     */
    public function find($collection, StorageFilterInterface $filter, array $fields = [], $hydrateObject = true)
    {
        $cursor = $this->findWithCursor($collection, $filter, $fields);

        $result = [];
        if ($cursor->count() > 0) {
            while ($cursor->hasNext()) {
                $item = $cursor->getNext();
                $id = $item['_id'];

                unset($item['_id']);

                $result[(string)$id] = $item;

                if ($hydrateObject && isset($item['_type'])) {
                    $result[(string)$id] = $this->hydrateResult($item);
                }
            }
        }

        return $result;
    }

    public function findWithCursor($collection, StorageFilterInterface $filter, array $fields = [])
    {
        $this->ensureConnection();

        $queryParams = $filter->getQueryParams();

        try {
            $cursor = $this->db->$collection
                ->find($queryParams, $fields)
                ->sort($filter->getSortParams())
                ->limit($filter->getLimit())
                ->skip($filter->getOffset());

            return $cursor;

        } catch (\Exception $e) {
            throw new StorageException('Can not retrieve data from storage: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count($collection, StorageFilterInterface $filter)
    {
        $this->ensureConnection();

        $queryParams = $filter->getQueryParams();

        try {
            $count = $this->db->$collection
                ->find($queryParams)
                ->count();
        } catch (\Exception $e) {
            throw new StorageException('Can not retrieve data from storage: ' . $e->getMessage(), $e->getCode(), $e);
        }

        return $count;
    }

    /**
     * Hydrates a resulting array object from a query
     *
     * @param array $result
     *
     * @return array|\JMS\Serializer\scalar|object
     */
    public function hydrateResult(array $result)
    {
        return $this->serializer->deserialize($result, SerializableInterface::class, 'mongo_array');
    }

    /**
     * Executes an aggregation pipeline on a given collection
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

        $data = $this->db->$collection->aggregate($pipeline, $options);

        if (!$data['ok']) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot aggregate on collection "%s": %s (Code: %d)',
                    $collection, $data['errmsg'], $data['code']
                )
            );
        }

        return $data['result'];
    }

    /**
     * Creates a new configuration using the current configuration options specified using the {@link configure()}
     * method
     *
     * @return \MongoClient
     * @see configure()
     */
    protected function createConnection()
    {
        return new \MongoClient(
            $this->configuration['host'],
            $this->configuration['options'],
            $this->configuration['driver_options']
        );
    }

    /**
     * {@inheritDoc}
     */
    public function doDestroy()
    {
        $this->disconnect();
    }

    /**
     * Calls the doDestroy method on kernel.terminate event
     *
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this->doDestroy();
    }

    /**
     * Calls the doDestroy method on console.terminate event
     *
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $this->doDestroy();
    }
}
