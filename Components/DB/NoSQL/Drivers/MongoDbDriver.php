<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\NoSQLMessage;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\NoSQLMessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class MongoDbDriver.
 */
class MongoDbDriver extends Service implements NoSQLDriverInterface, SerializableInterface
{
    /** @var MongoDBClient */
    protected $mongoClient;

    /**
     * MongoNoSQLDriver constructor.
     *
     * @param MongoDBClient $mongoStorage
     */
    public function __construct(MongoDBClient $mongoStorage)
    {
        $this->mongoClient = $mongoStorage;
    }

    /**
     * @return \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\NoSQLMessage
     */
    public function createMessage()
    {
        $message = new NoSQLMessage();
        $message->setContext(new Context([Context::FLOWS_VERSION => $this->getFlowsVersion()]));

        return $message;
    }

    /**
     * @param NoSQLMessageInterface $message
     *
     * @return string
     */
    public function create(NoSQLMessageInterface $message)
    {
        $collectionName = $message->getCollectionName();

        return $this->mongoClient->save($collectionName, $message->getBody());
    }

    /**
     * @param NoSQLMessageInterface $message
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function update(NoSQLMessageInterface $message)
    {
        throw new \Exception('Updating MongoDB is not yet implemented');
    }

    /**
     * @param NoSQLMessageInterface $message
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function delete(NoSQLMessageInterface $message)
    {
        throw new \Exception('Deleting from MongoDB is not yet implemented');
    }

    /**
     * @param string $collection
     * @param array  $query
     * @param array  $options
     *
     * @return null|NoSQLMessageInterface|array
     *
     * @throws \Exception
     */
    public function read($collection, array $query = [], $options = [])
    {
        throw new \Exception('Receiving from MongoDB is not yet implemented');
    }
}
