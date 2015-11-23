<?php

namespace Smartbox\Integration\FrameworkBundle\Storage\Driver;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Storage\Exception\DataStorageException;
use Smartbox\Integration\FrameworkBundle\Storage\Exception\StorageException;
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

        $optionsResolver
            ->setAllowedTypes('host', ['null', 'string'])
            ->setDefault('host', null);

        $optionsResolver
            ->setAllowedTypes('database', 'string');

        $optionsResolver->setRequired(['database']);

        try {
            $this->configuration = $optionsResolver->resolve($configuration);
            $this->connect();
        } catch (\Exception $e) {
            throw new StorageException('Wrong configuration: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * @throws StorageException
     */
    private function connect()
    {
        try {
            if (!is_array($this->configuration)) {
                throw new StorageException('Can not connect to MongoDB because configuration for this driver was not provided.');
            }

            if ($this->configuration['host']) {
                $this->connection = new \MongoClient($this->configuration['host']);
            } else {
                $this->connection = new \MongoClient();
            }
            $this->connection->connect();
        } catch(\Exception $e) {
            throw new StorageException('Can not connect to storage because of: ' . $e->getMessage(), $e->getCode(), $e);
        }

        $this->db = $this->connection->selectDB($this->configuration['database']);
    }

    private function disconnect()
    {
        if ($this->connection instanceof \MongoClient && $this->connection->connected)  {
            $this->connection->close(true);
        }
    }

    protected function checkConnection()
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
        $this->checkConnection();

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
        $this->checkConnection();

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

            return $this->serializer->deserialize($result, SerializableInterface::class, 'array');
        }

        return null;
    }
}