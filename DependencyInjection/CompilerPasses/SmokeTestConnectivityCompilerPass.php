<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\CompilerPasses;

use Smartbox\Integration\FrameworkBundle\Tools\SmokeTests\ConnectivityCheckSmokeTest;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class SmokeTestConnectivityCompilerPass.
 */
class SmokeTestConnectivityCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        $smokeTestCommand = $container->getDefinition('smartcore.command.smoke_test');
        $connectivityCheckSmokeTestClass = $container->getParameter('smartesb.smoke_test.connectivity_check.class');
        $connectivityCheckSmokeTestItems = $container->findTaggedServiceIds(ConnectivityCheckSmokeTest::TAG_TEST_CONNECTIVITY);
        foreach ($connectivityCheckSmokeTestItems as $serviceName => $tags) {
            $testServiceName = $serviceName.'.connectivity_smoke_test';
            $container->register($testServiceName, $connectivityCheckSmokeTestClass)
                ->setArguments([
                    'Connectivity check for '.$serviceName,
                    [$testServiceName => new Reference($serviceName)],
                ])
            ;

            $labels = [];

            foreach ($tags as $tag => $attr) {
                if (array_key_exists('labels', $attr)) {
                    $labels = explode(',', $attr['labels']);
                }
            }
            $smokeTestCommand->addMethodCall('addTest', [$testServiceName, new Reference($testServiceName), 'run', 'getDescription', $labels]);

            foreach ($container->getDefinitions() as $id => $definition) {
                $definition->setPublic(true);
            }

            foreach ($container->getAliases() as $id => $alias) {
                $alias->setPublic(true);
            }
        }
    }
}
