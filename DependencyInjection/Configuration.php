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
            ->scalarNode('events_log_level')
                ->defaultValue(LogLevel::DEBUG)
                ->validate()
                ->ifNotInArray([
                    LogLevel::EMERGENCY,
                    LogLevel::ALERT,
                    LogLevel::CRITICAL,
                    LogLevel::ERROR,
                    LogLevel::WARNING,
                    LogLevel::NOTICE,
                    LogLevel::INFO,
                    LogLevel::DEBUG,
                ])
                ->thenInvalid('Invalid log level for events log: "%s"')
            ->end()
            ->end()

            ->scalarNode('events_queue_name')
            ->defaultValue('smartesb_events_queue')->end()

            ->scalarNode('events_queue_driver')
            ->isRequired()->end()

            ->scalarNode('default_queue_driver')
            ->isRequired()->end()

            ->scalarNode('connectors_path')
            ->isRequired()->end()

            ->end()
            ->append($this->addConnectorsNode())
            ->append($this->addQueueDriversNode())
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

            ->scalarNode('type')
            ->info('Type of consumer (queue, file, db, ...)')
            ->defaultValue(5)
            ->isRequired()
            ->end()

            ->scalarNode('driver')
            ->info('The name of the driver to use')
            ->isRequired()
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
            ->useAttributeAsKey('name')
                ->prototype('variable')
                ->end()
            ->end();

        return $node;
    }

    public function addConnectorsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('connectors');
        $node->info("Section where the connectors are defined.");

        $node->useAttributeAsKey('name')
            ->prototype('array')
            ->children()

            ->scalarNode('description')
            ->info('This description will be used in the documentation.')
            ->isRequired()
            ->end()

            ->scalarNode('class')
            ->info('Class to be used for the connector, you can use a generic class like RESTConnector or create a custom class implementing ConfigurableConnectorInterface')
            ->isRequired()
            ->end()

            ->arrayNode('options')
            ->useAttributeAsKey('name')
            ->prototype('variable')
            ->end()
            ->info('Default options for this connector')
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

                ->arrayNode('response')
                    ->info('This defines how to generate the response')
                    ->prototype('variable')->end()
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
            ->end()
            ->isRequired();

        return $node;
    }

    public function addHandlersNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('message_handlers');
        $node->info("Section where the handlers are defined.");

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
        ->isRequired();

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
            ->defaultValue(5)
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
}
