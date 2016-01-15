<?php

namespace Smartbox\Integration\FrameworkBundle\Drivers\Db;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasType;
use Smartbox\Integration\FrameworkBundle\Messages\Db\NoSQLMessageInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Db\NoSQLMessage;
use Smartbox\Integration\FrameworkBundle\Storage\Driver\MongoDBStorage;

/**
 * Class MongoDbDriver
 * @package Smartbox\Integration\FrameworkBundle\Drivers\Db
 */
class MongoDbDriver implements NoSQLDriverInterface, SerializableInterface
{
    use HasType;

    /** @var MongoDBStorage */
    protected $mongoStorage;

    /**
     * MongoNoSQLDriver constructor.
     * @param MongoDBStorage $mongoStorage
     */
    public function __construct(MongoDBStorage $mongoStorage)
    {
        $this->mongoStorage = $mongoStorage;
    }

    /**
     * @param NoSQLMessageInterface $message
     * @return bool
     * @throws \Exception
     * @throws \Smartbox\Integration\FrameworkBundle\Storage\Exception\DataStorageException
     */
    public function send(NoSQLMessageInterface $message)
    {
        $collectionName = $message->getCollectionName();
        $this->mongoStorage->save($collectionName, $message);

        return true;
    }

    /**
     * Returns One Serializable object from the queue
     *
     * It requires to subscribe previously to a specific queue
     *
     * @return NoSQLMessageInterface|null
     * @throws \Exception
     */
    public function receive()
    {
        // TODO: Implement receive() method.
        throw new \Exception('Receiving from MongoDB is not yet implemented');
    }

    /**
     * @return NoSQLMessage
     */
    public function createMessage()
    {
        $message = new NoSQLMessage();
        $message->setCreatedAt(new \DateTime());

        return $message;
    }
}
