<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL;

use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\NoSQLDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\ExchangeEnvelope;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesDriverRegistry;

/**
 * Class NoSQLProducer.
 */
class NoSQLProducer extends Producer
{
    use UsesDriverRegistry;

    /**
     * {@inheritdoc}
     */
    public function send(Exchange $ex, EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        $message = $ex->getIn();

        $driverName = $options[NoSQLProtocol::OPTION_NOSQL_DRIVER];
        /** @var \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\NoSQLDriverInterface $driver */
        $driver = $this->getDriverRegistry()->getDriver($driverName);

        if (empty($driver) || !$driver instanceof NoSQLDriverInterface) {
            throw new \RuntimeException(self::class, NoSQLProtocol::OPTION_NOSQL_DRIVER, 'Expected NoSQLDriverInterface instance');
        }

        $collectionName = $options[NoSQLProtocol::OPTION_COLLECTION_PREFIX].$options[NoSQLProtocol::OPTION_COLLECTION_NAME];
        $success = false;

        switch ($options[NoSQLProtocol::OPTION_ACTION]) {
            case NoSQLProtocol::ACTION_CREATE:
                $success = $driver->insert($collectionName,$message);
                break;
            case NoSQLProtocol::ACTION_DELETE:
                //$success = $driver->delete($collectionName, $message);
                break;
            case NoSQLProtocol::ACTION_UPDATE:
                //$success = $driver->update($collectionName,$message, null);
                break;
            case NoSQLProtocol::ACTION_GET:
                throw new \Exception('Receiving from NOSQLProducer is not yet implemented');
                break;
        }

        if (!$success) {
            throw new \RuntimeException('The message could not be processed by NOSQLProducer');
        }
    }
}
