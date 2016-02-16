<?php

namespace Smartbox\Integration\FrameworkBundle\Helper;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\SmartboxIntegrationFrameworkExtension;
use Smartbox\Integration\FrameworkBundle\Drivers\Db\NoSQLDriverInterface;
use Smartbox\Integration\FrameworkBundle\Drivers\Queue\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Handlers\MessageHandler;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class SmartesbHelper
 *
 * @package Smartbox\Integration\FrameworkBundle\Helper
 */
class SmartesbHelper implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @return NoSQLDriverInterface
     */
    public function getNoSQLDriver($storageName)
    {
        $prefix = SmartboxIntegrationFrameworkExtension::NOSQL_DRIVER_PREFIX.$storageName;
        return $this->container->get($prefix);
    }

    /**
     * @param string $queueName
     * @return QueueDriverInterface
     */
    public function getQueueDriver($queueName)
    {
        $prefix = SmartboxIntegrationFrameworkExtension::QUEUE_DRIVER_PREFIX.$queueName;
        return $this->container->get($prefix);

    }

    /**
     * @param $handlerName
     * @return MessageHandler
     */
    public function getHandler($handlerName)
    {
        $prefix = SmartboxIntegrationFrameworkExtension::HANDLER_PREFIX.$handlerName;
        return $this->container->get($prefix);
    }

    /**
     * @param $consumerName
     * @return object
     */
    public function getConsumer($consumerName)
    {
        $prefix = SmartboxIntegrationFrameworkExtension::CONSUMER_PREFIX.$consumerName;
        return $this->container->get($prefix);
    }
}
