<?php

namespace Smartbox\Integration\FrameworkBundle\Storage;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Storage\Exception\StorageException;

interface StorageInterface
{
    /**
     * @param array $configuration
     *
     * @throws StorageException
     */
    public function configure(array $configuration);

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
}