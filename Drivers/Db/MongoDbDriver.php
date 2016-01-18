<?php

namespace Smartbox\Integration\FrameworkBundle\Drivers\Db;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Db\NoSQLMessageInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Db\NoSQLMessage;
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
     * {@inheritDoc}
     */
    public function send(NoSQLMessageInterface $message)
    {
        $collectionName = $message->getCollectionName();
        $this->mongoClient->save($collectionName, $message->getBody());

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function receive($collection, array $query = [], $options = [])
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
        $t = microtime(true);
        $micro = sprintf("%06d",($t - floor($t)) * 1000000);
        $d = new \DateTime( date('Y-m-d H:i:s.'.$micro,$t) );
        $message->setCreatedAt($d);

        return $message;
    }
}
