<?php

namespace Smartbox\Integration\FrameworkBundle\Drivers\Db;

use Smartbox\Integration\FrameworkBundle\Messages\Db\DbMessageInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Db\MongoDbMessage;
use Smartbox\Integration\FrameworkBundle\Storage\Driver\MongoDBStorage;

/**
 * Class MongoDbDriver
 * @package Smartbox\Integration\FrameworkBundle\Drivers\Db
 */
class MongoDbDriver implements DbDriverInterface
{
    /** @var MongoDBStorage */
    protected $mongoStorage;

    /**
     * MongoDbDriver constructor.
     * @param MongoDBStorage $mongoStorage
     */
    public function __construct(MongoDBStorage $mongoStorage)
    {
        $this->mongoStorage = $mongoStorage;
    }

    /**
     * @param DbMessageInterface $message
     * @return bool
     * @throws \Exception
     * @throws \Smartbox\Integration\FrameworkBundle\Storage\Exception\DataStorageException
     */
    public function send(DbMessageInterface $message)
    {
        if (!$message instanceof MongoDbMessage) {
            throw new \Exception(
                sprintf('Invalid message: expected instance of MongoDbMessage, "%s" given.', get_class($message))
            );
        }

        $collectionName = $message->getCollectionName();
        $this->mongoStorage->save($collectionName, $message);

        return true;
    }

    /**
     * Returns One Serializable object from the queue
     *
     * It requires to subscribe previously to a specific queue
     *
     * @return DbMessageInterface|null
     * @throws \Exception
     */
    public function receive()
    {
        // TODO: Implement receive() method.
        throw new \Exception('Receiving from MongoDB is not yet implemented');
    }

    /**
     * @return MongoDbMessage
     */
    public function createMessage()
    {
        $message = new MongoDbMessage();
        $message->setTimestamp(new \DateTime());

        return $message;
    }
}
