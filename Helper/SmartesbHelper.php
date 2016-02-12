<?php

namespace Smartbox\Integration\FrameworkBundle\Helper;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\SmartboxIntegrationFrameworkExtension;
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
     * @return object
     */
    public function getStorageDriver($storageName)
    {
        $prefix = SmartboxIntegrationFrameworkExtension::NOSQL_DRIVER_PREFIX.$storageName;
        return $this->container->get($prefix);
    }

    /**
     * @param string $queueName
     * @return object
     */
    public function getQueueDriver($queueName)
    {
        $prefix = SmartboxIntegrationFrameworkExtension::QUEUE_DRIVER_PREFIX.$queueName;
        return $this->container->get($prefix);

    }

    /**
     * @param $handlerName
     * @return object
     */
    public function getHandlerDriver($handlerName)
    {
        $prefix = SmartboxIntegrationFrameworkExtension::HANDLER_PREFIX.$handlerName;
        return $this->container->get($prefix);
    }

    /**
     * @param $consumerName
     * @return object
     */
    public function getConsumerDriver($consumerName)
    {
        $prefix = SmartboxIntegrationFrameworkExtension::CONSUMER_PREFIX.$consumerName;
        return $this->container->get($prefix);
    }
}
