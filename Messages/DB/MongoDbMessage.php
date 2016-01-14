<?php

namespace Smartbox\Integration\FrameworkBundle\Messages\Db;

use Smartbox\Integration\FrameworkBundle\Messages\Message;

/**
 * Class MongoDbMessage
 * @package Smartbox\Integration\FrameworkBundle\Messages\Db
 */
class MongoDbMessage extends Message implements MongoDbMessageInterface
{
    /** @var mixed */
    protected $id;

    /** @var mixed */
    protected $timestamp;

    /** @var Message */
    protected $message;

    /** @var string */
    protected $databaseName;

    /** @var string */
    protected $collectionName;

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * {@inheritDoc}
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * {@inheritDoc}
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    /**
     * {@inheritDoc}
     */
    public function setDatabaseName($databaseName)
    {
        $this->databaseName = $databaseName;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCollectionName()
    {
        return $this->collectionName;
    }

    /**
     * {@inheritDoc}
     */
    public function setCollectionName($collectionName)
    {
        $this->collectionName = $collectionName;
        return $this;
    }
}
