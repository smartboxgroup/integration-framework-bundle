<?php

namespace Smartbox\Integration\FrameworkBundle\Drivers\Db;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Messages\DB\NoSQLMessageInterface;
use Smartbox\Integration\FrameworkBundle\Messages\DB\NoSQLMessage;
use Smartbox\Integration\FrameworkBundle\Messages\NoSQLMessageEnvelope;
use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\Storage\Driver\MongoDBClient;

/**
 * Class MongoDbDriver
 * @package Smartbox\Integration\FrameworkBundle\Drivers\Db
 */
class MongoDbDriver extends Service implements NoSQLDriverInterface, SerializableInterface
{
    /** @var MongoDBClient */
    protected $mongoClient;

    /**
     * MongoNoSQLDriver constructor.
     * @param MongoDBClient $mongoStorage
     */
    public function __construct(MongoDBClient $mongoStorage)
    {
        $this->mongoClient = $mongoStorage;
    }

    /**
     * @return NoSQLMessage
     */
    public function createMessage()
    {
        $message = new NoSQLMessage();
        $message->setContext(new Context([Context::VERSION => $this->getFlowsVersion()]));

        return $message;
    }

    /**
     * @param NoSQLMessageInterface $message
     * @return string
     */
    public function create(NoSQLMessageInterface $message)
    {
        $collectionName = $message->getCollectionName();
        return $this->mongoClient->save($collectionName, $message->getBody());
    }

    /**
     * @param NoSQLMessageInterface $message
     * @return bool
     * @throws \Exception
     */
    public function update(NoSQLMessageInterface $message)
    {
        throw new \Exception('Updating MongoDB is not yet implemented');
    }

    /**
     * @param NoSQLMessageInterface $message
     * @return bool
     * @throws \Exception
     */
    public function delete(NoSQLMessageInterface $message)
    {
        throw new \Exception('Deleting from MongoDB is not yet implemented');
    }

    /**
     * @param string $collection
     * @param array $query
     * @param array $options
     * @return null|NoSQLMessageInterface|array
     * @throws \Exception
     */
    public function read($collection, array $query = [], $options = [])
    {
        throw new \Exception('Receiving from MongoDB is not yet implemented');
    }
}
