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
    const OPTION_ACTION = 'action';

    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_GET = 'get';

    /**
     * Get the connector default options
     * @return array
     */
    public function getDefaultOptions() {
        return array_merge(
            parent::getDefaultOptions(),
            [
                self::OPTION_ACTION => self::ACTION_CREATE
            ]
        );
    }

    /**
     * Sends an exchange to the connector
     *
     * @param Exchange $ex
     * @param array $options
     * @throws InvalidOptionException
     * @throws \Exception
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

        $success = false;

        switch($options[self::OPTION_ACTION]){
            case self::ACTION_CREATE:
                $success = $driver->create($message);
                break;
            case self::ACTION_DELETE:
                $success = $driver->delete($message);
                break;
            case self::ACTION_UPDATE:
                $success = $driver->update($message);
                break;
            case self::ACTION_GET:
                throw new \Exception("Receiving from NOSQLConnector is not yet implemented");
                break;
        }

        if(!$success){
            throw new \RuntimeException("The message could not be processed by NOSQLConnector");
        }
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
            self::OPTION_ACTION => ['Action to execute in the database',[
                self::ACTION_CREATE => 'Creates a record',
                self::ACTION_UPDATE => 'Updates a record (not supported yet)',
                self::ACTION_DELETE => 'Deletes a record (not supported yet)',
                self::ACTION_GET => 'Gets a record (not supported yet)',
            ]]
        ]);
    }
}