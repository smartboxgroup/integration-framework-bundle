<?php

namespace Smartbox\Integration\FrameworkBundle\Storage\Driver;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Storage\Exception\DataStorageException;
use Smartbox\Integration\FrameworkBundle\Storage\Exception\StorageException;
use Smartbox\Integration\FrameworkBundle\Storage\Filter\StorageFilterInterface;
use Smartbox\Integration\FrameworkBundle\Storage\StorageInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use JMS\Serializer\SerializerInterface;

class MongoDBStorage implements StorageInterface
{
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
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefined(['host', 'database']);
        $optionsResolver->setRequired(['host', 'database']);

        $optionsResolver
            ->setAllowedTypes('host', 'string')
            ->setAllowedTypes('database', 'string')
        ;

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
            $this->connection = new \MongoClient($this->configuration['host']);
            $this->connection->connect();
        } catch(\Exception $e) {
            throw new StorageException('Can not connect to storage because of: ' . $e->getMessage(), $e->getCode(), $e);
        }

        $this->db = $this->connection->selectDB($this->configuration['database']);
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->connection instanceof \MongoClient && $this->connection->connected)  {
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
        } catch(\Exception $e) {
            $exception = new DataStorageException('Can not save data to storage: ' . $e->getMessage(), $e->getCode(), $e);
            $exception->setStorageData($storageData);

            throw $exception;
        }

        return (string) $data['_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function findOne($collection, $id)
    {
        $this->ensureConnection();

        if (! \MongoId::isValid($id)) {
            return null;
        }

        try {
            $result = $this->db->$collection->findOne(['_id' => new \MongoId($id)]);
        } catch(\Exception $e) {
            throw new StorageException('Can not retrieve data from storage: ' . $e->getMessage(), $e->getCode(), $e);
        }

        if (!empty($result)) {
            unset($result['_id']);

            return $this->serializer->deserialize($result, SerializableInterface::class, 'mongo_array');
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function find($collection, StorageFilterInterface $filter, array $fields = [], $hydrateObject = true)
    {
        $this->ensureConnection();

        $queryParams = $filter->getQueryParams();

        try {
            $cursor = $this->db->$collection
                ->find($queryParams, $fields)
                ->sort($filter->getSortParams())
                ->limit($filter->getLimit())
                ->skip($filter->getOffset())
            ;
        } catch(\Exception $e) {
            throw new StorageException('Can not retrieve data from storage: ' . $e->getMessage(), $e->getCode(), $e);
        }

        $result = [];
        if ($cursor->count() > 0) {
            while($cursor->hasNext()) {
                $item = $cursor->getNext();
                $id = $item['_id'];

                unset($item['_id']);

                $result[(string) $id] = $item;

                if ($hydrateObject && isset($item['type'])) {
                    $result[(string) $id] = $this->serializer->deserialize($item, SerializableInterface::class, 'mongo_array');
                }
            }
        }

        return $result;
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
                ->count()
            ;
        } catch(\Exception $e) {
            throw new StorageException('Can not retrieve data from storage: ' . $e->getMessage(), $e->getCode(), $e);
        }

        return $count;
    }

    /**
     * Executes an aggregation pipeline on a given collection
     *
     * @param string $collection
     * @param array $pipeline
     * @param array $options
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
}