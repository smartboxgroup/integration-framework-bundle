<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection;

use Smartbox\Integration\FrameworkBundle\Connectors\ConfigurableConnectorInterface;
use Smartbox\Integration\FrameworkBundle\Consumers\QueueConsumer;
use Smartbox\Integration\FrameworkBundle\Drivers\Queue\ActiveMQStompQueueDriver;
use Smartbox\Integration\FrameworkBundle\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\Handlers\MessageHandlerTest;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SmartboxIntegrationFrameworkExtension extends Extension
{
    const DRIVER_PREFIX = 'smartesb.drivers.';
    const HANDLER_PREFIX = 'smartesb.handlers.';
    const CONSUMER_PREFIX = 'smartesb.consumers.';

    protected $config;

    public function getConnectorsPath(){
        return @$this->config['connectors_path'];
    }

    public function loadConnectors(ContainerBuilder $container){
        foreach ($this->config['connectors'] as $connectorName => $connectorConfig) {
            $class = $connectorConfig['class'];
            $methodsSteps = $connectorConfig['methods'];
            $options = $connectorConfig['options'];

            if (!$class || !in_array(ConfigurableConnectorInterface::class, class_implements($class))) {
                throw new InvalidConfigurationException(
                    "Invalid class given for connector $connectorName. The class must implement ConfigurableConnectorInterface, '$class' given."
                );
            }

            $definition = new Definition($class);
            $definition->addMethodCall('setMethodsConfiguration', [$methodsSteps]);
            $definition->addMethodCall('setDefaultOptions', [$options]);
            $definition->addMethodCall('setEvaluator',[new Reference('smartesb.util.evaluator')]);
            $definition->addMethodCall('setSerializer',[new Reference('serializer')]);

            $container->setDefinition('smartesb.connectors.'.$connectorName, $definition);
        }
    }

    public function loadMappings(ContainerBuilder $container){
        $mappings = $this->config['mappings'];
        if(!empty($mappings)){
            $mapper = $container->getDefinition('smartesb.util.mapper');
            $mapper->addMethodCall('addMappings',[$mappings]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->config = $this->processConfiguration($configuration, $configs);

        $eventQueueName = $this->config['events_queue_name'];
        $eventsLogLevel = $this->config['events_log_level'];
        $container->setParameter('smartesb.events_queue_name', $eventQueueName);
        $container->setParameter('smartesb.event_listener.events_logger.log_level', $eventsLogLevel);

        foreach($this->config['message_handlers'] as $handlerName => $handlerConfig){
            $handlerName = self::HANDLER_PREFIX.$handlerName;
            $def = new Definition(MessageHandler::class,array());

            $def->addMethodCall('setId', [$handlerName]);
            $def->addMethodCall('setEventDispatcher', [new Reference('event_dispatcher')]);
            $def->addMethodCall('setRetriesMax', [$handlerConfig['retries_max']]);
            $def->addMethodCall('setConnectorsRouter', [new Reference('smartesb.router.connectors')]);
            $def->addMethodCall('setItinerariesRouter', [new Reference('smartesb.router.itineraries')]);
            $def->addMethodCall('setFailedURI', [$handlerConfig['failed_uri']]);

            if($handlerConfig['retry_uri'] != 'original'){
                $def->addMethodCall('setRetryURI', [$handlerConfig['retry_uri']]);
            }else{
                $def->addMethodCall('setRetryURI', [false]);
            }

            $def->addMethodCall('setThrowExceptions',  [$handlerConfig['throw_exceptions']]);
            $def->addMethodCall('setDeferNewExchanges', [$handlerConfig['defer_new_exchanges']]);

            $def->addTag('kernel.event_listener', array(
                'event' => 'smartesb.exchange.new',
                'method' => 'onNewExchangeEvent'
            ));

            $container->setDefinition($handlerName,$def);
        }

        foreach($this->config['queue_drivers'] as $driverName => $driverConfig){
            $driverName = self::DRIVER_PREFIX.$driverName;

            switch($driverConfig['type']){
                case 'ActiveMQ':
                    $def = new Definition(ActiveMQStompQueueDriver::class,array());

                    $def->addMethodCall('setId', array($driverName));

                    $def->addMethodCall('configure', array(
                        $driverConfig['host'],
                        $driverConfig['username'],
                        $driverConfig['password'],
                        $driverConfig['format'],
                    ));

                    $def->addMethodCall('setSerializer', [new Reference('serializer')]);

                    $container->setDefinition($driverName,$def);
            }
        }

        foreach($this->config['message_consumers'] as $consumerName => $consumerConfig){
            $consumerName = self::CONSUMER_PREFIX.$consumerName;

            switch($consumerConfig['type']){
                case 'queue':
                    $def = new Definition(QueueConsumer::class,array());
                    $def->addMethodCall('setQueueDriver',[new Reference(self::DRIVER_PREFIX.$consumerConfig['driver'])]);
                    $def->addMethodCall('setHandler',[new Reference(self::HANDLER_PREFIX.$consumerConfig['handler'])]);
                    $container->setDefinition($consumerName,$def);
                    break;
            }
        }

        $defaultQueueDriverAlias = new Alias(self::DRIVER_PREFIX.$this->config['default_queue_driver']);
        $container->setAlias('smartesb.default_queue_driver',$defaultQueueDriverAlias);

        $eventsQueueDriverAlias = new Alias(self::DRIVER_PREFIX.$this->config['events_queue_driver']);
        $container->setAlias('smartesb.events_queue_driver',$eventsQueueDriverAlias);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('exceptions.yml');
        $loader->load('connectors.yml');
        $loader->load('services.yml');


        $this->loadConnectors($container);
        $this->loadMappings($container);
    }
}
