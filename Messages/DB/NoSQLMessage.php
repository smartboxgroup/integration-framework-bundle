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
    /** @var mixed */
    protected $id;

    /**
     * @var \DateTime
     * @JMS\Type("DateTime")
     * @JMS\Groups({"context", "logs"})
     * @JMS\Expose
     */
    protected $createdAt;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({"context", "logs"})
     * @JMS\Expose
     */
    protected $databaseName;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({"context", "logs"})
     * @JMS\Expose
     */
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
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * {@inheritDoc}
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;
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
