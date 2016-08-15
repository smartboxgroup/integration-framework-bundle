<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\NoSQLMessageInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\Storage\Query\QueryOptionsInterface;

/**
 * Interface NoSQLDriverInterface.
 */
interface NoSQLDriverInterface
{
    /**
     * Delete all items matching a given $queryOptions.
     *
     * @param string                $storageResourceName
     * @param QueryOptionsInterface $queryOptions
     */
    public function delete($storageResourceName, QueryOptionsInterface $queryOptions);

    /**
     * Find all items matching a given $queryOptions.
     *
     * @param string                $storageResourceName
     * @param QueryOptionsInterface $queryOptions
     * @param array                 $fields
     *
     * @return SerializableInterface[]|SerializableInterface|null
     */
    public function find($storageResourceName, QueryOptionsInterface $queryOptions, array $fields = []);

    /**
     * Insert an item.
     *
     * @param string                $storageResourceName
     * @param SerializableInterface $data
     *
     * @return string $id
     */
    public function insert($storageResourceName, SerializableInterface $data);

    /**
     * Update one or many items matching the $queryOptions with the given $data.
     *
     * @param string                $storageResourceName
     * @param QueryOptionsInterface $queryOptions
     * @param array $data
     *
     * @return mixed $updateResult
     */
    public function update($storageResourceName, QueryOptionsInterface $queryOptions, array $data);
}
