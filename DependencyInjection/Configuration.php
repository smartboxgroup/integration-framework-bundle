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
                ->scalarNode('events_queue')
                    ->defaultValue('smartesb_events_queue')
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }

}
