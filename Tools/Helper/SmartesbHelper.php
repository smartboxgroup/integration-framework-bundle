<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Helper;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\SmartboxIntegrationFrameworkExtension;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class SmartesbHelper.
 */
class SmartesbHelper implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @return \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\MongoDB\NoSQLDriverInterface
     */
    public function getNoSQLDriver($storageName)
    {
        $prefix = SmartboxIntegrationFrameworkExtension::NOSQL_DRIVER_PREFIX.$storageName;

        return $this->container->get($prefix);
    }

    /**
     * @param string $queueName
     *
     * @return QueueDriverInterface
     */
    public function getQueueDriver($queueName)
    {
        $prefix = SmartboxIntegrationFrameworkExtension::QUEUE_DRIVER_PREFIX.$queueName;

        return $this->container->get($prefix);
    }

    /**
     * @param $handlerName
     *
     * @return MessageHandler
     */
    public function getHandler($handlerName)
    {
        $prefix = SmartboxIntegrationFrameworkExtension::HANDLER_PREFIX.$handlerName;

        return $this->container->get($prefix);
    }

    /**
     * @param $consumerName
     *
     * @return object
     */
    public function getConsumer($consumerName)
    {
        $prefix = SmartboxIntegrationFrameworkExtension::CONSUMER_PREFIX.$consumerName;

        return $this->container->get($prefix);
    }

    /**
     * @return \Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory
     */
    public function getEndpointFactory()
    {
        return $this->container->get('smartesb.endpoint_factory');
    }

    /**
     * @return \Smartbox\Integration\FrameworkBundle\Core\Messages\MessageFactory
     */
    public function getMessageFactory()
    {
        return $this->container->get('smartesb.message_factory');
    }
}
