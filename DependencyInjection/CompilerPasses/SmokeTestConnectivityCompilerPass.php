<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\CompilerPasses;

use Smartbox\Integration\FrameworkBundle\Tools\SmokeTests\ConnectivityCheckSmokeTest;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class SmokeTestConnectivityCompilerPass
 *
 * @package Smartbox\Integration\FrameworkBundle\DependencyInjection
 */
class SmokeTestConnectivityCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        $connectivityCheckSmokeTestDef = $container->getDefinition('smartesb.smoke_test.connectivity_check');
        $connectivityCheckSmokeTestItems = $container->findTaggedServiceIds(ConnectivityCheckSmokeTest::TAG_TEST_CONNECTIVITY);
        foreach ($connectivityCheckSmokeTestItems as $serviceName => $tags) {
            $connectivityCheckSmokeTestDef->addMethodCall('addItem', array($serviceName, new Reference($serviceName)));
        }

        $smokeTestCommand = $container->getDefinition('smartcore.command.smoke_test');
        $smokeTestCommand->addMethodCall('addTest', ['smartesb.smoke_test.connectivity_check', new Reference('smartesb.smoke_test.connectivity_check')]);
    }
}
