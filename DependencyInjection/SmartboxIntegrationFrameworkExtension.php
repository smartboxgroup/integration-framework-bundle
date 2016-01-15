<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection;

use Smartbox\Integration\FrameworkBundle\Consumers\QueueConsumer;
use Smartbox\Integration\FrameworkBundle\Drivers\Db\MongoDbDriver;
use Smartbox\Integration\FrameworkBundle\Drivers\DriverRegistry;
use Smartbox\Integration\FrameworkBundle\Drivers\Queue\ActiveMQStompQueueDriver;
use Smartbox\Integration\FrameworkBundle\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Storage\Driver\MongoDBClient;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SmartboxIntegrationFrameworkExtension extends Extension
{
    const QUEUE_DRIVER_PREFIX = 'smartesb.drivers.queue.';
    const NOSQL_DRIVER_PREFIX = 'smartesb.drivers.nosql.';
    const HANDLER_PREFIX = 'smartesb.handlers.';
    const CONSUMER_PREFIX = 'smartesb.consumers.';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $eventQueueName = $config['events_queue_name'];
        $eventsLogLevel = $config['events_log_level'];
        $container->setParameter('smartesb.events_queue_name', $eventQueueName);
        $container->setParameter('smartesb.event_listener.events_logger.log_level', $eventsLogLevel);

        // Create services for message handlers
        foreach($config['message_handlers'] as $handlerName => $handlerConfig){
            $handlerName = self::HANDLER_PREFIX.$handlerName;
            $driverDef = new Definition(MessageHandler::class,array());

            $driverDef->addMethodCall('setId', [$handlerName]);
            $driverDef->addMethodCall('setEventDispatcher', [new Reference('event_dispatcher')]);
            $driverDef->addMethodCall('setRetriesMax', [$handlerConfig['retries_max']]);
            $driverDef->addMethodCall('setConnectorsRouter', [new Reference('smartesb.router.connectors')]);
            $driverDef->addMethodCall('setItinerariesRouter', [new Reference('smartesb.router.itineraries')]);
            $driverDef->addMethodCall('setFailedURI', [$handlerConfig['failed_uri']]);

            if($handlerConfig['retry_uri'] != 'original'){
                $driverDef->addMethodCall('setRetryURI', [$handlerConfig['retry_uri']]);
            }else{
                $driverDef->addMethodCall('setRetryURI', [false]);
            }

            $driverDef->addMethodCall('setThrowExceptions',  [$handlerConfig['throw_exceptions']]);
            $driverDef->addMethodCall('setDeferNewExchanges', [$handlerConfig['defer_new_exchanges']]);

            $driverDef->addTag('kernel.event_listener', array(
                'event' => 'smartesb.exchange.new',
                'method' => 'onNewExchangeEvent'
            ));

            $container->setDefinition($handlerName,$driverDef);
        }


        // Create services for message consumers
        foreach($config['message_consumers'] as $consumerName => $consumerConfig){
            $consumerName = self::CONSUMER_PREFIX.$consumerName;

            switch($consumerConfig['type']){
                case 'queue':
                    $driverDef = new Definition(QueueConsumer::class,array());
                    $driverDef->addMethodCall('setQueueDriver',[new Reference(self::QUEUE_DRIVER_PREFIX.$consumerConfig['driver'])]);
                    $driverDef->addMethodCall('setHandler',[new Reference(self::HANDLER_PREFIX.$consumerConfig['handler'])]);
                    $container->setDefinition($consumerName,$driverDef);

                    break;
            }
        }

        $queueDriverRegistry = new Definition(DriverRegistry::class);
        $container->setDefinition(self::QUEUE_DRIVER_PREFIX.'_registry',$queueDriverRegistry);

        // Create services for queue drivers
        foreach($config['queue_drivers'] as $driverName => $driverConfig){
            $driverId = self::QUEUE_DRIVER_PREFIX.$driverName;

            $type = strtolower($driverConfig['type']);
            switch($type){
                case 'activemq':
                    $driverDef = new Definition(ActiveMQStompQueueDriver::class, array());

                    $driverDef->addMethodCall('setId', array($driverId));

                    $driverDef->addMethodCall('configure', array(
                        $driverConfig['host'],
                        $driverConfig['username'],
                        $driverConfig['password'],
                        $driverConfig['format'],
                    ));

                    $driverDef->addMethodCall('setSerializer', [new Reference('serializer')]);
                    $queueDriverRegistry->addMethodCall('setDriver',[$driverName,new Reference($driverId)]);

                    $container->setDefinition($driverId,$driverDef);

                    break;

                default:
                    throw new InvalidDefinitionException(sprintf('Invalid queue driver type "%s"', $type));
            }
        }

        $nosqlDriverRegistry = new Definition(DriverRegistry::class);
        $container->setDefinition(self::NOSQL_DRIVER_PREFIX.'_registry',$nosqlDriverRegistry);

        // Create services for NoSQL drivers
        foreach($config['nosql_drivers'] as $driverName => $driverConfig) {
            $driverId = self::NOSQL_DRIVER_PREFIX.$driverName;

            $type = strtolower($driverConfig['type']);
            switch($type) {
                case 'mongodb':

                    $storageServiceName = $driverId . '.storage';
                    $storageDef = new Definition(MongoDBClient::class, [new Reference('serializer')]);

                    $mongoDriverOptions = [];
                    $connectionOptions = $driverConfig['connection_options'];
                    if (isset($connectionOptions['driver_options'])) {
                        $mongoDriverOptions = $connectionOptions['driver_options'];
                        unset($connectionOptions['driver_options']);
                    }

                    $storageDef->addMethodCall('configure', [[
                        'host'      => $driverConfig['host'],
                        'database'  => $driverConfig['database'],
                        'options'   => $connectionOptions,
                        'driver_options' => $mongoDriverOptions,
                    ]]);
                    $container->setDefinition($storageServiceName, $storageDef);

                    $driverDef = new Definition(MongoDbDriver::class, [new Reference($storageServiceName)]);
                    $container->setDefinition($driverId, $driverDef);

                    $nosqlDriverRegistry->addMethodCall('setDriver',[$driverName,new Reference($driverId)]);

                    break;

                default:
                    throw new InvalidDefinitionException(sprintf('Invalid NoSQL driver type "%s"', $type));
            }
        }

        // set default queue driver alias
        $defaultQueueDriverAlias = new Alias(self::QUEUE_DRIVER_PREFIX.$config['default_queue_driver']);
        $container->setAlias('smartesb.default_queue_driver',$defaultQueueDriverAlias);

        // set default events queue alias
        $eventsQueueDriverAlias = new Alias(self::QUEUE_DRIVER_PREFIX.$config['events_queue_driver']);
        $container->setAlias('smartesb.events_queue_driver',$eventsQueueDriverAlias);

        // set the default nosql driver
        if (null !== $config['default_nosql_driver']) {
            $noSQLDriverAlias = new Alias(self::NOSQL_DRIVER_PREFIX.$config['default_nosql_driver']);
            $container->setAlias('smartesb.default_nosql_driver', $noSQLDriverAlias);
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('exceptions.yml');
        $loader->load('connectors.yml');
        $loader->load('services.yml');
    }
}
