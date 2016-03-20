<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection;

use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const NAME = 'smartbox_integration_framework';

    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(self::NAME);
        $rootNode->children()
            ->enumNode('events_log_level')
                ->defaultValue(LogLevel::DEBUG)
                ->values([
                    LogLevel::EMERGENCY,
                    LogLevel::ALERT,
                    LogLevel::CRITICAL,
                    LogLevel::ERROR,
                    LogLevel::WARNING,
                    LogLevel::NOTICE,
                    LogLevel::INFO,
                    LogLevel::DEBUG,
                ])
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
            ->append($this->addQueueDriversNode())
            ->append($this->addNoSQLDriversNode())
            ->append($this->addConsumersNode())
            ->append($this->addHandlersNode())
            ->append($this->addMappingsNode())
        ->end();

        return $treeBuilder;
    }

    public function addConsumersNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('message_consumers');
        $node->info("Section where the message consumers are defined.");

        $node->useAttributeAsKey('name')
            ->prototype('array')
            ->children()

            ->scalarNode('description')
            ->info('This description will be used in the documentation.')
            ->defaultValue("")
            ->end()

            ->scalarNode('handler')
            ->info('The name of the message handler to use')
            ->isRequired()
            ->end()

            ->end()
            ->end()
            ->isRequired();

        return $node;
    }

    public function addMappingsNode(){

        $builder = new TreeBuilder();
        $node = $builder->root('mappings');

        $node
            ->info('Mappings to translate messages')
            ->useAttributeAsKey('__mapping_name')
                ->prototype('array')
                    ->prototype('scalar')->end()
                ->end()
            ->end();

        return $node;
    }

    public function addProducersNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('producers');
        $node->info("Section where the producers are defined.");

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

                ->arrayNode('steps')
                    ->info('This are the steps to execute as part of this method')
                    ->prototype('variable')->end()
                    ->isRequired()
                ->end()

                ->variableNode('response')
                    ->info('This defines how to generate the response')
                ->end()

                ->arrayNode('validations')
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
            ->defaultValue("")
            ->end()

            ->scalarNode('retries_max')
            ->info('Max number of times that the handler will retry to deliver a message failing in the same point of error')
            ->defaultValue(5)
            ->isRequired()
            ->end()

            ->scalarNode('failed_uri')
            ->info('The URI where the failed messages will be delivered.')
            ->isRequired()
            ->end()

            ->scalarNode('retry_uri')
            ->info('The URI where the messages pending of retrying will be delivered.')
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
            function($handlers) {
                return (!array_key_exists('sync',$handlers) || !array_key_exists('async',$handlers));
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
        $node->info("Section where the queue drivers are defined");

        $node->useAttributeAsKey('name')
            ->prototype('array')
            ->children()

                ->scalarNode('type')
                    ->info('Driver type (e.g.: ActiveMQ')
                    ->defaultValue("")
                ->end()

                ->scalarNode('description')
                    ->info('This description will be used in the documentation.')
                    ->defaultValue("")
                ->end()

                ->scalarNode('host')
                    ->isRequired()
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
                    ->defaultValue("")
                ->end()

                ->scalarNode('description')
                    ->info('This description will be used in the documentation.')
                    ->defaultValue("")
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
                        function($value) {
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
