<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL;

use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

/**
 * Interface NoSQLMessageInterface
 * @package Smartbox\Integration\FrameworkBundle\Core\Messages\DB
 */
interface NoSQLMessageInterface extends MessageInterface
{
    /**
     * Get the message id
     * @return mixed
     */
    public function getId();

    /**
     * Set the message id
     * @param mixed $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get the database name
     * @return string
     */
    public function getDatabaseName();

    /**
     * Set the database name
     * @param string $databaseName
     * @return $this
     */
    public function setDatabaseName($databaseName);

    /**
     * Get the collection name
     * @return string
     */
    public function getCollectionName();

    /**
     * Set the collection name
     * @param string $collectionName
     * @return $this
     */
    public function setCollectionName($collectionName);
}
