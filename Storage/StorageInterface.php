<?php

namespace Smartbox\Integration\FrameworkBundle\Storage;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Storage\Exception\StorageException;
use Smartbox\Integration\FrameworkBundle\Storage\Filter\StorageFilterInterface;

interface StorageInterface
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
     * @param string $storageName
     * @param SerializableInterface $data
     * @return string $id
     */
    public function save($storageName, SerializableInterface $data);

    /**
     * @param string $storageName
     * @param string $id
     * @return SerializableInterface|null
     */
    public function findOne($storageName, $id);

    /**
     * @param $collection
     * @param StorageFilterInterface $filter
     * @return \MongoCursor|array
     */
    public function find($collection, StorageFilterInterface $filter);

    /**
     * @param $collection
     * @param StorageFilterInterface $filter
     * @return int
     */
    public function count($collection, StorageFilterInterface $filter);
}