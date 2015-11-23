<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
                ->scalarNode('events_queue')
                    ->defaultValue('smartesb_events_queue')
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }

}
