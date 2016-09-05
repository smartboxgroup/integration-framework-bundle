<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Exceptions\NoSQLDriverException;

/**
 * Interface NoSQLDriverInterface.
 */
interface NoSQLDriverInterface
{
    /**
     * Configures the driver.
     *
     * @param array $configuration
     *
     * @return mixed
     */
    public function configure(array $configuration);

    /**
     * Delete all documents matching a given $queryOptions.
     *
     * @param string                $storageResourceName
     * @param QueryOptionsInterface $queryOptions
     */
    public function delete($storageResourceName, QueryOptionsInterface $queryOptions);

    /**
     * Find all documents matching a given $queryOptions.
     *
     * @param string                $storageResourceName
     * @param QueryOptionsInterface $queryOptions
     * @param array                 $fields
     *
     * @return SerializableInterface[]|SerializableInterface|null
     */
    public function find($storageResourceName, QueryOptionsInterface $queryOptions, array $fields = []);

    /**
     * @param $collection
     * @param QueryOptionsInterface $queryOptions
     * @param array                 $fields
     *
     * @return mixed
     */
    public function findOne($collection, QueryOptionsInterface $queryOptions, array $fields = []);

    /**
     * Insert an document.
     *
     * @param string $storageResourceName
     * @param mixed  $data
     *
     * @return string $id
     */
    public function insertOne($storageResourceName, $data);

    /**
     * Insert an document.
     *
     * @param string $storageResourceName
     * @param array  $data
     *
     * @return string $id
     */
    public function insertMany($storageResourceName, array $data);

    /**
     * Update one or many documents matching the $queryOptions with the given $data.
     *
     * @param string                $storageResourceName
     * @param QueryOptionsInterface $queryOptions
     * @param array                 $data
     *
     * @return mixed $updateResult
     */
    public function update($storageResourceName, QueryOptionsInterface $queryOptions, array $data);

    /**
     * @param $collection
     * @param QueryOptionsInterface $queryOptions
     *
     * @return int
     *
     * @throws NoSQLDriverException
     */
    public function count($collection, QueryOptionsInterface $queryOptions);

    /**
     * @param $collection
     * @param $id
     * @param array $fields
     *
     * @return mixed
     */
    public function findOneById($collection, $id, array $fields = []);

    /**
     * @param $collection
     * @param $id
     *
     * @return mixed
     */
    public function deleteById($collection, $id);
}
