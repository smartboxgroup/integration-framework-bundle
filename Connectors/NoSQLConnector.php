<?php

namespace Smartbox\Integration\FrameworkBundle\Connectors;

use Smartbox\Integration\FrameworkBundle\Drivers\Db\NoSQLDriverInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Traits\UsesDriverRegistry;

/**
 * Class NoSQLConnector
 * @package Smartbox\Integration\FrameworkBundle\Connectors
 */
class NoSQLConnector extends Connector
{
    use UsesDriverRegistry;

    const OPTION_NOSQL_DRIVER = 'nosql_driver';
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
     * @param array $options
     * @throws InvalidOptionException
     */
    public function send(Exchange $ex, array $options)
    {
        $msg = $ex->getIn();

        $driverOption = $options[self::OPTION_NOSQL_DRIVER];
        if(is_string($driverOption)){
            /** @var NoSQLDriverInterface $driver */
            $driver = $this->getDriverRegistry()->getDriver($driverOption);
        }else{
            /** @var NoSQLDriverInterface $driver */
            $driver = $driverOption;
        }

        if(empty($driver) || !$driver instanceof NoSQLDriverInterface){
            throw new InvalidOptionException(self::class, self::OPTION_NOSQL_DRIVER, 'Expected NoSQLDriverInterface instance');
        }

        $collectionName = (@$options[self::OPTION_COLLECTION_PREFIX]).$options[self::OPTION_COLLECTION_NAME];

        $message = $driver->createMessage();
        $message->setBody($msg);
        $message->setCollectionName($collectionName);
        $message->setHeader(Message::HEADER_FROM, $options[InternalRouter::KEY_URI]);

        // Take other headers from msg
        foreach($this->headersToPropagate as $header){
            if($msg->getHeader($header)){
                $message->setHeader($header,$msg->getHeader($header));
            }
        }

        $success = $driver->send($message);

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
            self::OPTION_NOSQL_DRIVER    => ['The driver service to use to connect to the MongoDb instance', []],
            self::OPTION_COLLECTION_PREFIX  => ['A string prefix used for collection names', []],
            self::OPTION_COLLECTION_NAME    => ['The name of the collection in which the messages will be stored', []],
        ]);
    }
}