<?php

namespace Smartbox\Integration\FrameworkBundle\Messages\Db;

/**
 * Interface MongoDbMessageInterface
 * @package Smartbox\Integration\FrameworkBundle\Messages\Db
 */
interface MongoDbMessageInterface extends DbMessageInterface
{
    /**
     * @param string $databaseName
     */
    public function setDatabaseName($databaseName);

    /**
     * @param string $collectionName
     */
    public function setCollectionName($collectionName);
}
