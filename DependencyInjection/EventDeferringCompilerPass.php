<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class EventDeferringCompilerPass
 * @package Smartbox\Integration\FrameworkBundle\DependencyInjection
 */
class EventDeferringCompilerPass implements CompilerPassInterface
{
    const TAG_EVENTS_FILTER = 'smartif.events.deferring_filter';

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        $filters = $container->findTaggedServiceIds(self::TAG_EVENTS_FILTER);
        $filtersRepoDef = $container->getDefinition('smartif.registry.event_filters');

        foreach($filters as $serviceName => $tags){
            $filtersRepoDef->addMethodCall('addDeferringFilter',array(new Reference($serviceName)));
        }
    }
}
