<?php

namespace Smartbox\Integration\FrameworkBundle\Messages\Db;

use Smartbox\Integration\FrameworkBundle\Messages\Message;
use JMS\Serializer\Annotation as JMS;

/**
 * Class NoSQLMessage
 * @package Smartbox\Integration\FrameworkBundle\Messages\Db
 */
class NoSQLMessage extends Message implements NoSQLMessageInterface
{
    const HEADER_COLLECTION_NAME = 'collection_name';
    const HEADER_DATABASE_NAME = 'database_name';
    const HEADER_MONGO_ID = 'mongo_id';

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->getHeader(self::HEADER_MONGO_ID);
    }

    /**
     * {@inheritDoc}
     */
    public function setId($id)
    {
        $this->setHeader(self::HEADER_MONGO_ID,$id);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDatabaseName()
    {
        return $this->getHeader(self::HEADER_DATABASE_NAME);
    }

    /**
     * {@inheritDoc}
     */
    public function setDatabaseName($databaseName)
    {
        $this->setHeader(self::HEADER_DATABASE_NAME,$databaseName);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCollectionName()
    {
        return $this->getHeader(self::HEADER_COLLECTION_NAME);
    }

    /**
     * {@inheritDoc}
     */
    public function setCollectionName($collectionName)
    {
        $this->setHeader(self::HEADER_COLLECTION_NAME,$collectionName);
        return $this;
    }
}
