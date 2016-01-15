<?php

namespace Smartbox\Integration\FrameworkBundle\Connectors;

use JMS\Serializer\SerializerInterface;
use Smartbox\Integration\FrameworkBundle\Drivers\Db\MongoDbDriver;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;

/**
 * Class MongoDbConnector
 * @package Smartbox\Integration\FrameworkBundle\Connectors
 */
class MongoDbConnector extends Connector
{
    const OPTION_MONGO_DB_DRIVER = 'db_driver';
    const OPTION_COLLECTION_PREFIX = 'prefix';
    const OPTION_COLLECTION_NAME = 'collection';

    protected $headersToPropagate = [
        Message::HEADER_VERSION
    ];

    protected $defaultOptions = [];

    /**
     * Sends an exchange to the connector
     *
     * @param Exchange $ex
     * @throws \Exception
     */
    public function send(Exchange $ex, array $options)
    {
        $msg = $ex->getIn();

        /** @var MongoDbDriver $mongoDriver */
        $mongoDriver = $options[self::OPTION_MONGO_DB_DRIVER];

        if(empty($mongoDriver) || !$mongoDriver instanceof MongoDbDriver){
            throw new InvalidOptionException(self::class, self::OPTION_MONGO_DB_DRIVER, 'Expected MongoDbDriver instance');
        }

        $collectionName = (@$options[self::OPTION_COLLECTION_PREFIX]).$options[self::OPTION_COLLECTION_NAME];

        $message = $mongoDriver->createMessage();
        $message->setBody($msg);
        $message->setCollectionName($collectionName);
        $message->setHeader(Message::HEADER_FROM, $options[InternalRouter::KEY_URI]);

        // Take other headers from msg
        foreach($this->headersToPropagate as $header){
            if($msg->getHeader($header)){
                $message->setHeader($header,$msg->getHeader($header));
            }
        }

        $success = $mongoDriver->send($message);

        if(!$success){
            throw new \RuntimeException("The message could not be delivered to the database");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultOptions()
    {
        return array_merge(
            parent::getDefaultOptions(),
            $this->defaultOptions
        );
    }

    /**
     * Get default options
     *
     * @return array Array with option name, description, and options (optional)
     */
    function getAvailableOptions()
    {
        return array_merge(parent::getAvailableOptions(), [
            self::OPTION_MONGO_DB_DRIVER    => ['The driver service to use to connect to the MongoDb instance', []],
            self::OPTION_COLLECTION_PREFIX  => ['A string prefix used for collection names', []],
            self::OPTION_COLLECTION_NAME    => ['The name of the collection in which the messages will be stored', []],
        ]);
    }
}