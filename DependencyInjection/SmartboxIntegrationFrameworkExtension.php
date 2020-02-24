<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\MongoDB\MongoDBDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\PhpAmqpLibDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\StompQueueDriver;
use Smartbox\Integration\FrameworkBundle\Configurability\DriverRegistry;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConfigurableConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ConfigurableProducerInterface;
use Smartbox\Integration\FrameworkBundle\Events\ExternalSystemHTTPEvent;
use Smartbox\Integration\FrameworkBundle\Events\MalformedInputEvent;
use Smartbox\Integration\FrameworkBundle\Events\HandlerErrorEvent;
use Smartbox\Integration\FrameworkBundle\Events\HandlerEvent;
use Smartbox\Integration\FrameworkBundle\Events\ProcessEvent;
use Smartbox\Integration\FrameworkBundle\Events\ProcessingErrorEvent;
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
    const EVENTS_LOGGER_ID = 'smartesb.event_listener.events_logger';
    const QUEUE_DRIVER_PREFIX = 'smartesb.drivers.queue.';
    const NOSQL_DRIVER_PREFIX = 'smartesb.drivers.nosql.';
    const HANDLER_PREFIX = 'smartesb.handlers.';
    const PRODUCER_PREFIX = 'smartesb.producers.';
    const CONSUMER_PREFIX = 'smartesb.consumers.';
    const PARAM_DEFERRED_EVENTS_URI = 'smartesb.uris.deferred_events';
    const AMQP_SERVICES = [
        'smartesb.consumers.async_queue',
        'smartesb.drivers.queue.phpamqplib',
        'smartesb.async_consumers.queue.class'
    ];

    protected $config;
    protected $useAmqp = false;

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

    public function loadConfigurableProducers(ContainerBuilder $container)
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
                        if (is_string($arg) && 0 === strpos($arg, '@')) {
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
            $definition->addMethodCall('setConfHelper', [new Reference('smartesb.configurable_service_helper')]);
            $definition->addMethodCall('setMessageFactory', [new Reference('smartesb.message_factory')]);
            $definition->addMethodCall('setEvaluator', [new Reference('smartesb.util.evaluator')]);
            $definition->addMethodCall('setSerializer', [new Reference('jms_serializer')]);
            $definition->addMethodCall('setEventDispatcher', [new Reference('event_dispatcher')]);
            $definition->addMethodCall('setName', [$producerName]);
            $container->setDefinition($producerId, $definition);

            if (in_array(CanCheckConnectivityInterface::class, class_implements($definition->getClass()))) {
                $attrs = [
                    'labels' => call_user_func([$definition->getClass(), 'getConnectivitySmokeTestLabels']),
                ];
                $definition->addTag(ConnectivityCheckSmokeTest::TAG_TEST_CONNECTIVITY, $attrs);
            }
        }
    }

    public function loadConfigurableConsumers(ContainerBuilder $container)
    {
        foreach ($this->config['consumers'] as $consumerName => $consumerConfig) {
            $class = $consumerConfig['class'];
            $methodsConf = $consumerConfig['methods'];
            $options = $consumerConfig['options'];

            if (!$class || !in_array(ConfigurableConsumerInterface::class, class_implements($class))) {
                throw new InvalidConfigurationException(
                    "Invalid class given for consumer $consumerName. The class must implement ConfigurableConsumerInterface, '$class' given."
                );
            }

            $definition = new Definition($class);

            if (array_key_exists('calls', $consumerConfig)) {
                foreach ($consumerConfig['calls'] as $call) {
                    $method = $call[0];
                    $arguments = $call[1];
                    $resolvedArguments = [];
                    foreach ($arguments as $index => $arg) {
                        $resolvedArguments[$index] = $arg;

                        if (0 === strpos($arg, '@')) {
                            $resolvedArguments[$index] = new Reference(substr($arg, 1));
                        }
                    }

                    $definition->addMethodCall($method, $resolvedArguments);
                }
            }

            $consumerId = self::CONSUMER_PREFIX.$consumerName;
            $definition->addMethodCall('setId', [$consumerId]);
            $definition->addMethodCall('setMethodsConfiguration', [$methodsConf]);
            $definition->addMethodCall('setSmartesbHelper', [new Reference('smartesb.helper')]);
            $definition->addMethodCall('setConfHelper', [new Reference('smartesb.configurable_service_helper')]);
            $definition->addMethodCall('setOptions', [$options]);
            $definition->addMethodCall('setEvaluator', [new Reference('smartesb.util.evaluator')]);
            $definition->addMethodCall('setSerializer', [new Reference('jms_serializer')]);
            $definition->addMethodCall('setMessageFactory', [new Reference('smartesb.message_factory')]);
            $definition->addMethodCall('setEventDispatcher', [new Reference('event_dispatcher')]);
            $definition->addMethodCall('setName', [$consumerName]);
            $container->setDefinition($consumerId, $definition);

            if (in_array(CanCheckConnectivityInterface::class, class_implements($definition->getClass()))) {
                $attrs = [
                    'labels' => call_user_func([$definition->getClass(), 'getConnectivitySmokeTestLabels']),
                ];
                $definition->addTag(ConnectivityCheckSmokeTest::TAG_TEST_CONNECTIVITY, $attrs);
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

            $exceptionHandlerId = strtolower($driverConfig['exception_handler']);

            $type = strtolower($driverConfig['type']);
            switch ($type) {
                case 'rabbitmq':
                case 'activemq':
                case 'stomp':
                    $urlEncodeDestination = ('rabbitmq' == $type);

                    $driverDef = new Definition(StompQueueDriver::class, []);
                    $driverDef->addMethodCall('setId', [$driverId]);

                    $driverDef->addMethodCall('configure', [
                        $driverConfig['host'],
                        $driverConfig['username'],
                        $driverConfig['password'],
                        $driverConfig['format'],
                        StompQueueDriver::STOMP_VERSION,
                        $driverConfig['vhost'],
                        $driverConfig['timeout'],
                        $driverConfig['sync'],
                    ]);

                    $driverDef->addMethodCall('setDescription', [$driverConfig['description']]);
                    $driverDef->addMethodCall('setSerializer', [new Reference('jms_serializer')]);
                    $driverDef->addMethodCall('setUrlEncodeDestination', [$urlEncodeDestination]);
                    $driverDef->addMethodCall('setMessageFactory', [new Reference('smartesb.message_factory')]);
                    if ($exceptionHandlerId) {
                        $driverDef->addMethodCall('setExceptionHandler', [new Reference($exceptionHandlerId)]);
                    }
                    $queueDriverRegistry->addMethodCall('setDriver', [$driverName, new Reference($driverId)]);
                    $driverDef->addTag('kernel.event_listener', ['event' => KernelEvents::TERMINATE, 'method' => 'onKernelTerminate']);
                    $driverDef->addTag('kernel.event_listener', ['event' => ConsoleEvents::TERMINATE, 'method' => 'onConsoleTerminate']);

                    $container->setDefinition($driverId, $driverDef);

                    break;

                case 'amqp':
                    $this->useAmqp = true;
                    
                    if (!class_exists(AMQPStreamConnection::class)) {
                        throw new \RuntimeException('You need the amqp extension to use PHP-AMQP-LIB driver.');
                    }

                    if (empty($driverConfig['connections'] ?? [])) {
                        throw new \InvalidArgumentException('You need to define at least one connection to use the PHP AMQP lib driver.');
                    }

                    $driverDef = new Definition(PhpAmqpLibDriver::class);

                    foreach ($driverConfig['connections'] as $index => $uri) {
                        $connection = parse_url($uri);

                        $driverDef->addMethodCall('configure', [
                            'host' => $connection['host'],
                            'username' => $connection['user'],
                            'password' => $connection['pass'],
                            'format' => $driverConfig['format'],
                            'port' => $connection['port'] ?? 5672,
                            'vhost' => trim($connection['path'] ?? '', '/'),
                        ]);
                    }

                    $queueDriverRegistry->addMethodCall('setDriver', [$driverName, $driverDef]);

                    $driverDef->addMethodCall('setId', [$driverId]);
                    $driverDef->addMethodCall('setSerializer', [new Reference('jms_serializer')]);

                    $container->setDefinition($driverId, $driverDef);

                    $consumer = $container->findDefinition('smartesb.async_consumers.queue');
                    $consumer->addMethodCall('setDriver', [$driverDef]);
                    $consumer->addMethodCall('setSerializer', [new Reference('jms_serializer')]);

                    $container->findDefinition('smartesb.protocols.queue')
                        ->addMethodCall('setDefaultConsumer', [$consumer]);

                    if ($exceptionHandlerId) {
                        $driverDef->addMethodCall('setExceptionHandler', [new Reference($exceptionHandlerId)]);
                        $consumer->addMethodCall('setExceptionHandler', [new Reference($exceptionHandlerId)]);
                    }
                    break;

                default:
                    throw new InvalidDefinitionException(sprintf('Invalid queue driver type "%s"', $type));
                    break;
            }
        }

        // set default queue driver alias
        $defaultQueueDriverAlias = new Alias(self::QUEUE_DRIVER_PREFIX.$this->config['default_queue_driver']);
        $container->setAlias('smartesb.default_queue_driver', $defaultQueueDriverAlias);

        if (!$this->useAmqp) {
            foreach (static::AMQP_SERVICES as $id) {
                $container->removeDefinition($id);
            }
        }
    }

    protected function loadNoSQLDrivers(ContainerBuilder $container)
    {
        $noSqlDriverRegistry = new Definition(DriverRegistry::class);
        $container->setDefinition(self::NOSQL_DRIVER_PREFIX.'_registry', $noSqlDriverRegistry);

        // Create services for NoSQL drivers
        foreach ($this->config['nosql_drivers'] as $driverName => $driverConfig) {
            $driverId = self::NOSQL_DRIVER_PREFIX.$driverName;

            $type = strtolower($driverConfig['type']);
            switch ($type) {
                case 'mongodb':

                    $driverDef = new Definition(MongoDBDriver::class);

                    $connectionOptions = $driverConfig['connection_options'];
                    if (isset($connectionOptions['driver_options'])) {
                        $mongoDriverOptions = $connectionOptions['driver_options'];
                        unset($connectionOptions['driver_options']);
                    }
                    $configuration = [
                        'host' => $driverConfig['host'],
                        'database' => $driverConfig['database'],
                        'options' => $connectionOptions,
                    ];

                    if (isset($mongoDriverOptions)) {
                        $configuration['driver_options'] = $mongoDriverOptions;
                    }
                    $driverDef->addMethodCall('configure', [$configuration]);

                    $driverDef->addTag('kernel.event_listener', ['event' => KernelEvents::TERMINATE, 'method' => 'onKernelTerminate']);
                    $driverDef->addTag('kernel.event_listener', ['event' => ConsoleEvents::TERMINATE, 'method' => 'onConsoleTerminate']);

                    $container->setDefinition($driverId, $driverDef);

                    $noSqlDriverRegistry->addMethodCall('setDriver', [$driverName, new Reference($driverId)]);

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

    protected function loadHandlers(ContainerBuilder $container)
    {
        // Create services for message handlers
        foreach ($this->config['message_handlers'] as $handlerName => $handlerConfig) {
            $handlerName = self::HANDLER_PREFIX.$handlerName;
            $handlerDef = new Definition(MessageHandler::class, []);

            $handlerDef->addMethodCall('setId', [$handlerName]);
            $handlerDef->addMethodCall('setContainer', [new Reference('service_container')]);
            $handlerDef->addMethodCall('setEventDispatcher', [new Reference('event_dispatcher')]);
            $handlerDef->addMethodCall('setEndpointFactory', [new Reference('smartesb.endpoint_factory')]);
            $handlerDef->addMethodCall('setItineraryResolver', [new Reference('smartesb.itineray_resolver')]);
            $handlerDef->addMethodCall('setFailedURI', [$handlerConfig['failed_uri']]);
            $handlerDef->addMethodCall('setCallbackURI', [$handlerConfig['callback_uri']]);
            $handlerDef->addMethodCall('setMessageFactory', [new Reference('smartesb.message_factory')]);

            // Settings for retry mechanism
            $handlerDef->addMethodCall('setRetriesMax', [$handlerConfig['retries_max']]);
            $handlerDef->addMethodCall('setRetryDelay', [$handlerConfig['retry_delay']]);
            $handlerDef->addMethodCall('setRetryStrategy', [$handlerConfig['retry_strategy']]);
            $handlerDef->addMethodCall('setRetryDelayFactor', [$handlerConfig['retry_delay_factor']]);
            if ('original' != $handlerConfig['retry_uri']) {
                $handlerDef->addMethodCall('setRetryURI', [$handlerConfig['retry_uri']]);
            } else {
                $handlerDef->addMethodCall('setRetryURI', [false]);
            }

            // Settings for throttle mechanism
            $handlerDef->addMethodCall('setThrottleDelay', [$handlerConfig['throttle_delay']]);
            $handlerDef->addMethodCall('setThrottleStrategy', [$handlerConfig['throttle_strategy']]);
            $handlerDef->addMethodCall('setThrottleDelayFactor', [$handlerConfig['throttle_delay_factor']]);

            // If there is no throttle_uri set, we need to set it with something, so we will use the retry uri.
            // This will maintains the current behaviour, but allows us to explicitly set a throttle_uri
            $throttleUri = isset($handlerConfig['throttle_uri']) ? $handlerConfig['throttle_uri'] : $handlerConfig['retry_uri'];
            $handlerDef->addMethodCall('setThrottleURI', [$throttleUri]);

            $handlerDef->addMethodCall('setThrowExceptions', [$handlerConfig['throw_exceptions']]);
            $handlerDef->addMethodCall('setDeferNewExchanges', [$handlerConfig['defer_new_exchanges']]);

            $handlerDef->addTag('kernel.event_listener', [
                'event' => 'smartesb.exchange.new',
                'method' => 'onNewExchangeEvent',
            ]);

            $container->setDefinition($handlerName, $handlerDef);
        }
    }

    public function enableLogging(ContainerBuilder $container)
    {
        $def = new Definition('%smartesb.event_listener.events_logger.class%', [
            new Reference('monolog.logger.tracking'),
            new Reference('request_stack'),
        ]);

        $def->addMethodCall('setEventsLogLevel', ['%smartesb.event_listener.events_logger.events_log_level%']);
        $def->addMethodCall('setErrorsLogLevel', ['%smartesb.event_listener.events_logger.errors_log_level%']);

        $def->addTag('kernel.event_listener', [
            'event' => HandlerEvent::BEFORE_HANDLE_EVENT_NAME,
            'method' => 'onEvent',
        ]);

        $def->addTag('kernel.event_listener', [
            'event' => ProcessEvent::TYPE_BEFORE,
            'method' => 'onEvent',
        ]);

        $def->addTag('kernel.event_listener', [
            'event' => ProcessingErrorEvent::EVENT_NAME,
            'method' => 'onEvent',
        ]);

        $def->addTag('kernel.event_listener', [
            'event' => ProcessEvent::TYPE_AFTER,
            'method' => 'onEvent',
        ]);

        $def->addTag('kernel.event_listener', [
            'event' => HandlerEvent::AFTER_HANDLE_EVENT_NAME,
            'method' => 'onEvent',
        ]);

        $def->addTag('kernel.event_listener', [
            'event' => HandlerEvent::UNRECOVERABLE_FAILED_EXCHANGE_EVENT_NAME,
            'method' => 'onEvent',
        ]);

        $def->addTag('kernel.event_listener', [
            'event' => HandlerEvent::RECOVERABLE_FAILED_EXCHANGE_EVENT_NAME,
            'method' => 'onEvent',
        ]);

        $def->addTag('kernel.event_listener', [
            'event' => HandlerEvent::CALLBACK_HANDLE_EVENT_NAME,
            'method' => 'onEvent',
        ]);

        $def->addTag('kernel.event_listener', [
            'event' => HandlerEvent::THROTTLING_HANDLE_EVENT_NAME,
            'method' => 'onEvent',
        ]);

        $def->addTag('kernel.event_listener', [
            'event' => HandlerErrorEvent::EVENT_NAME,
            'method' => 'onEvent',
        ]);

        $def->addTag('kernel.event_listener', [
            'event' => ExternalSystemHTTPEvent::EVENT_NAME,
            'method' => 'onEvent',
        ]);

        $def->addTag('kernel.event_listener', [
            'event' => MalformedInputEvent::EVENT_NAME,
            'method' => 'onEvent',
        ]);

        $container->setDefinition(self::EVENTS_LOGGER_ID, $def);
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
                sprintf(
                    'The flows version number(%s) can not be bigger than the latest version available(%s)',
                    $this->getFlowsVersion(),
                    $this->getLatestFlowsVersion()
                )
            );
        }

        $container->setParameter('smartesb.flows_version', $this->getFlowsVersion());

        $eventsLogLevel = $config['events_log_level'];
        $container->setParameter('smartesb.event_listener.events_logger.events_log_level', $eventsLogLevel);

        $errorsLogLevel = $config['errors_log_level'];
        $container->setParameter('smartesb.event_listener.events_logger.errors_log_level', $errorsLogLevel);

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

        // FEATURE FLAGS
        $container->setParameter('smartesb.enable_events_deferring', $config['enable_events_deferring']);

        if ($config['enable_logging']) {
            $this->enableLogging($container);
        }

        $queueProtocolDef = $container->getDefinition('smartesb.protocols.queue');
        $queueProtocolDef->setArguments([$config['queues_default_persistence'], $config['queues_default_ttl']]);

        $this->loadHandlers($container);
        $this->loadConfigurableConsumers($container);
        $this->loadQueueDrivers($container);
        $this->loadNoSQLDrivers($container);
        $this->loadConfigurableProducers($container);
        $this->loadMappings($container);
    }
}
