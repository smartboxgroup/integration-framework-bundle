<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Parser;

/**
 * Class EventDeferringCompilerPass
 * @package Smartbox\Integration\FrameworkBundle\DependencyInjection
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
        /** @var SmartboxIntegrationFrameworkExtension $extension */
        $extension = $container->getExtension('smartbox_integration_framework');

        $parser = new Parser();

        $finder = new Finder();
        $finder->files()->in($extension->getConnectorsPath());

        /** @var SplFileInfo $file */
        foreach($finder as $file){
            $parsed = $parser->parse(file_get_contents($file->getRealPath()));

            $name = @$parsed['name'];
            $class = @$parsed['class']; // Must implement ConfigurableConnectorInterface
            $mappings = @$parsed['mappings'];
            $methodsSteps = @$parsed['methods'];

            $definition = new Definition($class);
            $definition->addMethodCall('setMethodsConfiguration',[$methodsSteps]);
            $definition->addMethodCall('setMappings'[$mappings]);

            $container->set('smartesb.connectors.'.$name,$definition);
        }
    }
}
