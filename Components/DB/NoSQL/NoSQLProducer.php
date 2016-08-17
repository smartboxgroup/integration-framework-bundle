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

        $driverName = $options[NoSQLConfigurableProtocol::OPTION_NOSQL_DRIVER];
        /** @var \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\NoSQLDriverInterface $driver */
        $driver = $this->getDriverRegistry()->getDriver($driverName);

        if (empty($driver) || !$driver instanceof NoSQLDriverInterface) {
            throw new \RuntimeException(self::class, NoSQLConfigurableProtocol::OPTION_NOSQL_DRIVER, 'Expected NoSQLDriverInterface instance');
        }

        $collectionName = $options[NoSQLConfigurableProtocol::OPTION_COLLECTION_PREFIX].$options[NoSQLConfigurableProtocol::OPTION_COLLECTION_NAME];
        $success = false;

        switch ($options[NoSQLConfigurableProtocol::OPTION_ACTION]) {
            case NoSQLConfigurableProtocol::ACTION_INSERT:
                $success = $driver->insert($collectionName,$message);
                break;
            case NoSQLConfigurableProtocol::ACTION_DELETE:
                throw new \Exception("Action Delete from NOSQLProducer is not yet implemented");
                break;
            case NoSQLConfigurableProtocol::ACTION_UPDATE:
                throw new \Exception('Updating from NOSQLProducer is not yet implemented');
                break;
            case NoSQLConfigurableProtocol::ACTION_FIND:
                throw new \Exception('Receiving from NOSQLProducer is not yet implemented');
                break;
        }

        if (!$success) {
            throw new \RuntimeException('The message could not be processed by NOSQLProducer');
        }
    }
}
