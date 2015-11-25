<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SmartboxIntegrationFrameworkExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $eventQueueName = $config['events_queue'];
        $eventsLogLevel = $config['events_log_level'];

        $container->setParameter('smartesb.events_queue_name', $eventQueueName);
        $container->setParameter('smartesb.event_listener.events_logger.log_level', $eventsLogLevel);


        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('exceptions.yml');
        $loader->load('connectors.yml');
        $loader->load('services.yml');
    }
}
