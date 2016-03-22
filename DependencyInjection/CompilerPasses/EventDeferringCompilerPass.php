<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\CompilerPasses;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class EventDeferringCompilerPass.
 */
class EventDeferringCompilerPass implements CompilerPassInterface
{
    const TAG_EVENTS_FILTER = 'smartesb.events.deferring_filter';

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
        $filtersRepoDef = $container->getDefinition('smartesb.registry.event_filters');

        foreach ($filters as $serviceName => $tags) {
            $filtersRepoDef->addMethodCall('addDeferringFilter', array(new Reference($serviceName)));
        }
    }
}
