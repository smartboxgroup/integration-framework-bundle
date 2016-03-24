<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection;

use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\MongoDBClient;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\MongoDbDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\ActiveMQStompQueueDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueConsumer;
use Smartbox\Integration\FrameworkBundle\Configurability\DriverRegistry;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ConfigurableProducerInterface;
use Smartbox\Integration\FrameworkBundle\Tools\SmokeTests\CanCheckConnectivityInterface;
use Smartbox\Integration\FrameworkBundle\Tools\SmokeTests\ConnectivityCheckSmokeTest;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SmartboxIntegrationFrameworkExtension extends Extension
{
    const QUEUE_DRIVER_PREFIX = 'smartesb.drivers.queue.';
    const NOSQL_DRIVER_PREFIX = 'smartesb.drivers.nosql.';
    const HANDLER_PREFIX = 'smartesb.handlers.';
    const PRODUCER_PREFIX = 'smartesb.producers.';
    const CONSUMER_PREFIX = 'smartesb.consumers.';
    const PARAM_DEFERRED_EVENTS_URI = 'smartesb.uris.deferred_events';

    protected $config;

    public function getFlowsVersion()
    {
        return (string) $this->config['flows_version'];
    }

    public function getLatestFlowsVersion()
    {
        return $this->config['latest_flows_version'];
    }

    public function getProducersPath()
    {
        return @$this->config['producers_path'];
    }

    public function loadProducers(ContainerBuilder $container)
    {
        foreach ($this->config['producers'] as $producerName => $producerConfig) {
            $class = $producerConfig['class'];
            $methodsSteps = $producerConfig['methods'];
            $options = $producerConfig['options'];

            if (!$class || !in_array(ConfigurableProducerInterface::class, class_implements($class))) {
                throw new InvalidConfigurationException(
                    "Invalid class given for producer $producerName. The class must implement ConfigurableProducerInterface, '$class' given."
                );
            }

            $definition = new Definition($class);

            if (array_key_exists('calls', $producerConfig)) {
                foreach ($producerConfig['calls'] as $call) {
                    $method = $call[0];
                    $arguments = $call[1];
                    $resolvedArguments = [];
                    foreach ($arguments as $index => $arg) {
                        if (strpos($arg, '@') === 0) {
                            $resolvedArguments[$index] = new Reference(substr($arg, 1));
                        } else {
                            $resolvedArguments[$index] = $arg;
                        }
                    }

                    $definition->addMethodCall($method, $resolvedArguments);
                }
            }

            $producerId = self::PRODUCER_PREFIX.$producerName;
            $definition->addMethodCall('setId', [$producerId]);
            $definition->addMethodCall('setMethodsConfiguration', [$methodsSteps]);
            $definition->addMethodCall('setOptions', [$options]);
            $definition->addMethodCall('setEvaluator', [new Reference('smartesb.util.evaluator')]);
            $definition->addMethodCall('setSerializer', [new Reference('serializer')]);
            $container->setDefinition($producerId, $definition);

            if (in_array(CanCheckConnectivityInterface::class, class_implements($definition->getClass()))) {
                $definition->addTag(ConnectivityCheckSmokeTest::TAG_TEST_CONNECTIVITY);
            }
        }
    }

    public function loadMappings(ContainerBuilder $container)
    {
        $mappings = $this->config['mappings'];
        if (!empty($mappings)) {
            $mapper = $container->getDefinition('smartesb.util.mapper');
            $mapper->addMethodCall('addMappings', [$mappings]);
        }
    }

    protected function loadQueueDrivers(ContainerBuilder $container)
    {
        $queueDriverRegistry = new Definition(DriverRegistry::class);
        $container->setDefinition(self::QUEUE_DRIVER_PREFIX.'_registry', $queueDriverRegistry);

        // Create services for queue drivers
        foreach ($this->config['queue_drivers'] as $driverName => $driverConfig) {
            $driverId = self::QUEUE_DRIVER_PREFIX.$driverName;

            $type = strtolower($driverConfig['type']);
            switch ($type) {
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
                    $driverDef->addMethodCall('setMessageFactory', [new Reference('smartesb.message_factory')]);
                    $queueDriverRegistry->addMethodCall('setDriver', [$driverName, new Reference($driverId)]);
                    $driverDef->addTag('kernel.event_listener', ['event' => KernelEvents::TERMINATE, 'method' => 'onKernelTerminate']);
                    $driverDef->addTag('kernel.event_listener', ['event' => ConsoleEvents::TERMINATE, 'method' => 'onConsoleTerminate']);

                    $container->setDefinition($driverId, $driverDef);

                    break;

                default:
                    throw new InvalidDefinitionException(sprintf('Invalid queue driver type "%s"', $type));
            }
        }

        // set default queue driver alias
        $defaultQueueDriverAlias = new Alias(self::QUEUE_DRIVER_PREFIX.$this->config['default_queue_driver']);
        $container->setAlias('smartesb.default_queue_driver', $defaultQueueDriverAlias);
    }

    protected function loadNoSQLDrivers(ContainerBuilder $container)
    {
        $nosqlDriverRegistry = new Definition(DriverRegistry::class);
        $container->setDefinition(self::NOSQL_DRIVER_PREFIX.'_registry', $nosqlDriverRegistry);

        // Create services for NoSQL drivers
        foreach ($this->config['nosql_drivers'] as $driverName => $driverConfig) {
            $driverId = self::NOSQL_DRIVER_PREFIX.$driverName;

            $type = strtolower($driverConfig['type']);
            switch ($type) {
                case 'mongodb':

                    $storageServiceName = $driverId.'.storage';
                    $storageDef = new Definition(MongoDBClient::class, [new Reference('serializer')]);

                    $mongoDriverOptions = [];
                    $connectionOptions = $driverConfig['connection_options'];
                    if (isset($connectionOptions['driver_options'])) {
                        $mongoDriverOptions = $connectionOptions['driver_options'];
                        unset($connectionOptions['driver_options']);
                    }

                    $storageDef->addMethodCall('configure', [[
                        'host' => $driverConfig['host'],
                        'database' => $driverConfig['database'],
                        'options' => $connectionOptions,
                        'driver_options' => $mongoDriverOptions,
                    ]]);
                    $storageDef->addTag('kernel.event_listener', ['event' => KernelEvents::TERMINATE, 'method' => 'onKernelTerminate']);
                    $storageDef->addTag('kernel.event_listener', ['event' => ConsoleEvents::TERMINATE, 'method' => 'onConsoleTerminate']);

                    $container->setDefinition($storageServiceName, $storageDef);

                    $driverDef = new Definition(MongoDbDriver::class, [new Reference($storageServiceName)]);
                    $driverDef->addMethodCall('setMessageFactory', [new Reference('smartesb.message_factory')]);
                    $container->setDefinition($driverId, $driverDef);

                    $nosqlDriverRegistry->addMethodCall('setDriver', [$driverName, new Reference($driverId)]);

                    break;

                default:
                    throw new InvalidDefinitionException(sprintf('Invalid NoSQL driver type "%s"', $type));
            }
        }

        // set the default nosql driver
        if (null !== $this->config['default_nosql_driver']) {
            $noSQLDriverAlias = new Alias(self::NOSQL_DRIVER_PREFIX.$this->config['default_nosql_driver']);
            $container->setAlias('smartesb.default_nosql_driver', $noSQLDriverAlias);
        }
    }

    protected function loadConsumers(ContainerBuilder $container)
    {
        // Create services for message consumers
        foreach ($this->config['message_consumers'] as $consumerName => $consumerConfig) {
            $consumerId = self::CONSUMER_PREFIX.$consumerName;
            $consumerDef = new Definition(QueueConsumer::class, []);
            $consumerDef->addMethodCall('setId', [$consumerId]);
            $consumerDef->addMethodCall('setSmartesbHelper', [new Reference('smartesb.helper')]);
            $consumerDef->addMethodCall('setHandler', [new Reference(self::HANDLER_PREFIX.$consumerConfig['handler'])]);
            $container->setDefinition($consumerId, $consumerDef);
        }
    }

    protected function loadHandlers(ContainerBuilder $container)
    {
        // Create services for message handlers
        foreach ($this->config['message_handlers'] as $handlerName => $handlerConfig) {
            $handlerName = self::HANDLER_PREFIX.$handlerName;
            $driverDef = new Definition(MessageHandler::class, array());

            $driverDef->addMethodCall('setId', [$handlerName]);
            $driverDef->addMethodCall('setEventDispatcher', [new Reference('event_dispatcher')]);
            $driverDef->addMethodCall('setRetriesMax', [$handlerConfig['retries_max']]);
            $driverDef->addMethodCall('setEndpointFactory', [new Reference('smartesb.endpoint_factory')]);
            $driverDef->addMethodCall('setItinerariesRouter', [new Reference('smartesb.router.itineraries')]);
            $driverDef->addMethodCall('setFailedURI', [$handlerConfig['failed_uri']]);
            $driverDef->addMethodCall('setMessageFactory', [new Reference('smartesb.message_factory')]);

            if ($handlerConfig['retry_uri'] != 'original') {
                $driverDef->addMethodCall('setRetryURI', [$handlerConfig['retry_uri']]);
            } else {
                $driverDef->addMethodCall('setRetryURI', [false]);
            }

            $driverDef->addMethodCall('setThrowExceptions',  [$handlerConfig['throw_exceptions']]);
            $driverDef->addMethodCall('setDeferNewExchanges', [$handlerConfig['defer_new_exchanges']]);

            $driverDef->addTag('kernel.event_listener', array(
                'event' => 'smartesb.exchange.new',
                'method' => 'onNewExchangeEvent',
            ));

            $container->setDefinition($handlerName, $driverDef);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $this->config = $config;

        if ($this->getFlowsVersion() > $this->getLatestFlowsVersion()) {
            throw new InvalidConfigurationException(
                sprintf('The flows version number(%s) can not be bigger than the latest version available(%s)',
                    $this->getFlowsVersion(),
                    $this->getLatestFlowsVersion()));
        }

        $container->setParameter('smartesb.flows_version', $this->getFlowsVersion());

        $eventsLogLevel = $config['events_log_level'];
        $container->setParameter('smartesb.event_listener.events_logger.log_level', $eventsLogLevel);

        $eventsDeferToURI = $config['defer_events_to_uri'];
        $container->setParameter(self::PARAM_DEFERRED_EVENTS_URI, $eventsDeferToURI);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('exceptions.yml');
        $loader->load('protocols.yml');
        $loader->load('producers.yml');
        $loader->load('consumers.yml');
        $loader->load('events_deferring.yml');
        $loader->load('routing.yml');
        $loader->load('smoke_tests.yml');

        $this->loadHandlers($container);
        $this->loadConsumers($container);
        $this->loadQueueDrivers($container);
        $this->loadNoSQLDrivers($container);
        $this->loadProducers($container);
        $this->loadMappings($container);
    }
}
