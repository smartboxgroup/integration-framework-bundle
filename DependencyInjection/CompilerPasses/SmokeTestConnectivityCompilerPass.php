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
     * You can modify the container here before it is dumped to PHP code.
     *
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
            $smokeTestCommand->addMethodCall('addTest', [$testServiceName, new Reference($testServiceName)]);
        }
    }
}
