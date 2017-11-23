<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection;

use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConfigurableConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ConfigurableProducerInterface;
use Smartbox\Integration\FrameworkBundle\Tools\Logs\EventsLoggerListener;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration.
 */
class Configuration implements ConfigurationInterface
{
    const NAME = 'smartbox_integration_framework';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(self::NAME);
        $rootNode->children()
            ->enumNode('events_log_level')
            ->defaultValue(EventsLoggerListener::DEFAULT_EVENTS_LEVEL)
            ->values(EventsLoggerListener::getAvailableEventsLogLevel())
            ->end()

            ->enumNode('errors_log_level')
            ->defaultValue(EventsLoggerListener::DEFAULT_ERRORS_LEVEL)
            ->values(EventsLoggerListener::getAvailableErrorsLogLevel())
            ->end()

            ->scalarNode('enable_events_deferring')
            ->info('Feature flag for events deferring. True to enable, false to disable.')
            ->defaultValue(true)
            ->end()

            ->scalarNode('enable_logging')
            ->info('Feature flag for events logging. True to enable, false to disable.')
            ->defaultValue(true)
            ->end()

            ->scalarNode('queues_default_persistence')
            ->info('Use persistent messages for queues by default or not')
            ->defaultValue(true)
            ->end()

            ->scalarNode('queues_default_ttl')
            ->info('Default value for TTL of messages when they are sent to queues')
            ->defaultValue(86400)
            ->end()

            ->scalarNode('defer_events_to_uri')
            ->isRequired()->end()

            ->scalarNode('default_queue_driver')
            ->isRequired()->end()

            ->scalarNode('default_nosql_driver')
            ->isRequired()->end()

            ->scalarNode('flows_version')
            ->isRequired()->cannotBeEmpty()->end()

            ->scalarNode('latest_flows_version')
            ->defaultValue(0)->end()

            ->end()
            ->append($this->addProducersNode())
            ->append($this->addConsumersNode())
            ->append($this->addQueueDriversNode())
            ->append($this->addNoSQLDriversNode())
            ->append($this->addHandlersNode())
            ->append($this->addMappingsNode())
            ->end();

        return $treeBuilder;
    }

    public function addConsumersNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('consumers');
        $node->info('Section where the consumers are defined.');

        $node->useAttributeAsKey('name')
            ->prototype('array')
            ->children()

            ->scalarNode('description')
            ->info('This description will be used in the documentation.')
            ->isRequired()
            ->end()

            ->scalarNode('class')
            ->info('Class to be used for the consumer, you can use a generic class like MongoDBConfigurableConsumer or create a custom class implementing ConfigurableConsumerInterface')
            ->isRequired()
            ->end()

            ->arrayNode('calls')
            ->prototype('variable')->end()
            ->info('Additional calls to inject dependencies to the consumer')
            ->end()

            ->arrayNode('options')
            ->info('Default options for this consumer')
            ->useAttributeAsKey('name')
            ->prototype('variable')
            ->end()
            ->end()

            ->arrayNode('methods')
            ->info('List of methods with their configuration')
            ->useAttributeAsKey('name')
            ->prototype('array')

            ->children()

            ->scalarNode(ConfigurableServiceHelper::KEY_DESCRIPTION)
            ->info('This description will be used in the documentation.')
            ->isRequired()
            ->end()

            ->arrayNode(ConfigurableConsumerInterface::CONFIG_QUERY_STEPS)
            ->info('This are the steps that should be executed to fetch the data')
            ->prototype('variable')->end()
            ->isRequired()
            ->end()

            ->arrayNode(ConfigurableConsumerInterface::CONFIG_QUERY_RESULT)
            ->info('This will be evaluated to obtain the body of the message that is being consumed')
            ->prototype('variable')->end()
            ->isRequired()
            ->end()

            ->arrayNode(ConfigurableConsumerInterface::CONFIG_ON_CONSUME)
            ->info('This are the steps to execute after every message is consumed. For example here you can mark them as consumed')
            ->prototype('variable')->end()
            ->isRequired()
            ->end()

            ->end()
            ->end()

            ->info('Methods specification')
            ->isRequired()
            ->end()

            ->end();

        return $node;
    }

    public function addMappingsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('mappings');

        $node
            ->info('Mappings to translate messages')
            ->useAttributeAsKey('__mapping_name')
            ->prototype('array')
            ->prototype('variable')->end()
            ->end()
            ->end();

        return $node;
    }

    public function addProducersNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('producers');
        $node->info('Section where the producers are defined.');

        $node->useAttributeAsKey('name')
            ->prototype('array')
            ->children()

            ->scalarNode('description')
            ->info('This description will be used in the documentation.')
            ->isRequired()
            ->end()

            ->scalarNode('class')
            ->info('Class to be used for the producer, you can use a generic class like RestConfigurableProducer or create a custom class implementing ConfigurableProducerInterface')
            ->isRequired()
            ->end()

            ->arrayNode('calls')
            ->prototype('variable')->end()
            ->info('Additional calls to inject dependencies to the producer')
            ->end()

            ->arrayNode('options')
            ->useAttributeAsKey('name')
            ->prototype('variable')
            ->end()
            ->info('Default options for this producer')
            ->isRequired()
            ->end()

            ->arrayNode('methods')
            ->info('List of methods with their configuration')
            ->useAttributeAsKey('name')
            ->prototype('array')

            ->children()
            ->scalarNode('description')
            ->info('This description will be used in the documentation.')
            ->isRequired()
            ->end()

            ->arrayNode(ConfigurableProducerInterface::CONF_STEPS)
            ->info('This are the steps to execute as part of this method')
            ->prototype('variable')->end()
            ->isRequired()
            ->end()

            ->variableNode(ConfigurableProducerInterface::CONF_RESPONSE)
            ->info('This defines how to generate the response')
            ->end()

            ->arrayNode(ConfigurableProducerInterface::CONF_VALIDATIONS)
            ->info('Here you can specify validation rules with their related error message')
            ->prototype('array')
            ->children()
            ->scalarNode('rule')
            ->info('This is a Symfony expression that must evaluate to true to pass the validation')
            ->isRequired()
            ->end()
            ->scalarNode('message')
            ->info('This is the error message that should go in the generated exception')
            ->isRequired()
            ->end()
            ->booleanNode('recoverable')
            ->info('This is marks this error as recoverable or not')
            ->isRequired()
            ->end()
            ->end()
            ->end()
            ->end()

            ->end()
            ->isRequired()

            ->end()
            ->info('Methods specification')
            ->isRequired()
            ->end()

            ->end()
            ->end();

        return $node;
    }

    public function addHandlersNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('message_handlers');
        $node->info("Section where the handlers are defined. You must define at least two: 'sync' and 'async'");

        $node->useAttributeAsKey('name')
            ->prototype('array')
            ->children()

            ->scalarNode('description')
            ->info('This description will be used in the documentation.')
            ->defaultValue('')
            ->end()

            ->scalarNode('retries_max')
            ->info('Max number of times that the handler will retry to deliver a message failing in the same point of error')
            ->defaultValue(5)
            ->isRequired()
            ->end()

            ->scalarNode('retry_delay')
            ->info('Minimum delay in seconds used by the handler between two retry of the same message')
            ->defaultValue(0)
            ->isRequired()
            ->end()

            ->enumNode('retry_strategy')
            ->info('Retry strategy to use when retrying the same message')
            ->defaultValue(MessageHandler::RETRY_STRATEGY_FIXED)
            ->values(MessageHandler::getAvailableRetryStrategies())
            ->end()

            ->scalarNode('retry_delay_factor')
            ->info('Retry delay factor to be applied to the retry delay if the chosen strategy is progressive')
            ->defaultValue(1)
            ->end()

            ->scalarNode('retry_uri')
            ->info('The URI where the messages pending of retrying will be delivered.')
            ->isRequired()
            ->end()

            ->scalarNode('throttle_delay')
            ->info('Minimum delay in seconds used by the handler between two throttle of the same message')
            ->defaultValue(1)
            ->end()

            ->enumNode('throttle_strategy')
            ->info('Throttle strategy to use when throttling the same message')
            ->defaultValue(MessageHandler::RETRY_STRATEGY_FIXED)
            ->values(MessageHandler::getAvailableThrottleStrategies())
            ->end()

            ->scalarNode('throttle_delay_factor')
            ->info('throttle delay factor to be applied to the throttle delay if the chosen strategy is progressive')
            ->defaultValue(1)
            ->end()

            ->scalarNode('throttle_uri')
            ->info('The URI where the messages pending of throttling will be delivered. If no value is supplied, the retry_uri will be used')
            ->end()

            ->scalarNode('failed_uri')
            ->info('The URI where the failed messages will be delivered.')
            ->isRequired()
            ->end()

            ->scalarNode('throw_exceptions')
            ->info('Throw exceptions on errors and break the process or not')
            ->isRequired()
            ->end()

            ->scalarNode('defer_new_exchanges')
            ->info('Defer new exchanges or not')
            ->isRequired()
            ->end()

            ->end()
            ->end()
            ->isRequired()
            ->validate()->ifTrue(
                function ($handlers) {
                    return !array_key_exists('sync', $handlers) || !array_key_exists('async', $handlers);
                })
            ->thenInvalid('You must define at least two handlers, called "sync" and "async" in the smartesb configuration.')
            ->end()
        ;

        return $node;
    }

    public function addQueueDriversNode()
    {
        $builder = new TreeBuilder();

        $node = $builder->root('queue_drivers');
        $node->info('Section where the queue drivers are defined');

        $node->useAttributeAsKey('name')
            ->prototype('array')
            ->children()

            ->scalarNode('type')
            ->info('Driver type (e.g.: ActiveMQ')
            ->defaultValue('')
            ->end()

            ->scalarNode('description')
            ->info('This description will be used in the documentation.')
            ->defaultValue('')
            ->end()

            ->scalarNode('host')
            ->isRequired()
            ->end()

            ->scalarNode('vhost')
            ->info('This is the virtual host to use. By default is determined based on the host')
            ->defaultValue(null)
            ->end()

            ->scalarNode('username')
            ->isRequired()
            ->end()

            ->scalarNode('password')
            ->isRequired()
            ->end()

            ->scalarNode('format')
            ->isRequired()
            ->end()

            ->scalarNode('timeout')
            ->defaultValue(null)
            ->end()

            ->scalarNode('sync')
            ->info('This parameter define if the stomp driver will be synchronous or not')
            ->defaultValue(true)
            ->end()

            ->end()

            ->end()
            ->isRequired();

        return $node;
    }

    public function addNoSQLDriversNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('nosql_drivers');
        $node->info('Section where the nosql db drivers are defined');

        $node->useAttributeAsKey('name')
            ->prototype('array')
            ->children()

            ->scalarNode('type')
            ->info('Driver type (e.g.: MongoDB')
            ->defaultValue('')
            ->end()

            ->scalarNode('description')
            ->info('This description will be used in the documentation.')
            ->defaultValue('')
            ->end()

            ->scalarNode('host')
            ->isRequired()
            ->end()

            ->scalarNode('database')
            ->isRequired()
            ->end()

            ->variableNode('connection_options')
            ->defaultValue(null)
            ->validate()->ifTrue(
                function ($value) {
                    return !(is_array($value) || $value === null);
                })
            ->thenInvalid('Invalid connection options it should be an array or null')
            ->end()
            ->end()

            ->end()

            ->end()
            ->isRequired()
        ;

        return $node;
    }
}
