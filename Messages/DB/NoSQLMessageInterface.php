<?php

namespace Smartbox\Integration\FrameworkBundle\Messages\Db;

use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;

/**
 * Interface NoSQLMessageInterface
 * @package Smartbox\Integration\FrameworkBundle\Messages\Db
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
     * Get the creation date timestamp
     * @return mixed
     */
    public function getCreatedAt();

    /**
     * Set the creation date timestamp
     * @param \DateTime|null $timestamp
     * @return $this
     */
    public function setCreatedAt(\DateTime $timestamp = null);

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
