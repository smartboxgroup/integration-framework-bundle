<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\Storage;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\Storage\Exception\StorageException;
use Smartbox\Integration\FrameworkBundle\Components\DB\Storage\Query\QueryOptionsInterface;

/**
 * Interface StorageClientInterface
 *
 * @package Smartbox\Integration\FrameworkBundle\Components\DB\Storage
 */
interface StorageClientInterface
{
    /**
     * @param array $configuration
     *
     * @throws StorageException
     */
    public function configure(array $configuration);

    /**
     * Open connection to storage driver
     *
     * @throws StorageException
     */
    public function connect();

    /**
     * Close connection to storage driver
     *
     * @return void
     */
    public function disconnect();

    /**
     * @param string $storageResourceName
     * @param SerializableInterface $data
     * @return string $id
     */
    public function save($storageResourceName, SerializableInterface $data);

    /**
     * @param string $storageResourceName
     * @param QueryOptionsInterface $queryOptions
     * @param array $fields
     * @return SerializableInterface[]|SerializableInterface|null
     */
    public function find($storageResourceName, QueryOptionsInterface $queryOptions, array $fields = []);

    /**
     * @param string    $storageResourceName
     * @param QueryOptionsInterface $queryOptions
     * @param array $fields
     *
     * @return SerializableInterface|null
     */
    public function findOne($storageResourceName, QueryOptionsInterface $queryOptions, array $fields = []);

    /**
     * @param string $storageResourceName
     * @param string $id
     * @return SerializableInterface|null
     */
    public function findOneById($storageResourceName, $id);

    /**
     * Count items matching a given $filter
     *
     * @param string $storageResourceName
     * @param QueryOptionsInterface $queryOptions
     * @return int
     */
    public function count($storageResourceName, QueryOptionsInterface $queryOptions);

    /**
     * Delete all items matching a given $filter
     *
     * @param string $storageResourceName
     * @param QueryOptionsInterface $queryOptions
     */
    public function delete($storageResourceName, QueryOptionsInterface $queryOptions);

    /**
     * Delete a single record matching a given $id
     *
     * @param string $storageResourceName
     * @param string $id
     */
    public function deleteById($storageResourceName, $id);

    /**
     * Clean all the opened resources, must be called just before terminating the current request
     */
    public function doDestroy();
}
