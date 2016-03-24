<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL;

use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;

/**
 * Class NoSQLMessage.
 */
class NoSQLMessage extends Message implements NoSQLMessageInterface
{
    const HEADER_COLLECTION_NAME = 'collection_name';
    const HEADER_DATABASE_NAME = 'database_name';
    const HEADER_MONGO_ID = 'mongo_id';

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getHeader(self::HEADER_MONGO_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->setHeader(self::HEADER_MONGO_ID, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseName()
    {
        return $this->getHeader(self::HEADER_DATABASE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setDatabaseName($databaseName)
    {
        $this->setHeader(self::HEADER_DATABASE_NAME, $databaseName);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionName()
    {
        return $this->getHeader(self::HEADER_COLLECTION_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setCollectionName($collectionName)
    {
        $this->setHeader(self::HEADER_COLLECTION_NAME, $collectionName);

        return $this;
    }
}
