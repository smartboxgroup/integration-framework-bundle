<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers;

use Smartbox\CoreBundle\Type\SerializableInterface;

/**
 * Interface NoSQLDriverInterface.
 */
interface NoSQLDriverInterface
{
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
     * Insert an document.
     *
     * @param string                $storageResourceName
     * @param SerializableInterface $data
     *
     * @return string $id
     */
    public function insertOne($storageResourceName, SerializableInterface $data);


    /**
     * Insert an document.
     *
     * @param string                $storageResourceName
     * @param SerializableInterface[] $data
     *
     * @return string $id
     */
    public function insertMany($storageResourceName, array $data);

    /**
     * Update one or many documents matching the $queryOptions with the given $data.
     *
     * @param string                $storageResourceName
     * @param QueryOptionsInterface $queryOptions
     * @param array $data
     *
     * @return mixed $updateResult
     */
    public function update($storageResourceName, QueryOptionsInterface $queryOptions, array $data);
}
