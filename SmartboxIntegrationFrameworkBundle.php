<?php

namespace Smartbox\Integration\FrameworkBundle;

use Smartbox\Integration\FrameworkBundle\DependencyInjection\CompilerPasses\EventDeferringCompilerPass;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\CompilerPasses\ExpressionLanguageCachePass;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\CompilerPasses\MockWebserviceClientsCompilerPass;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\CompilerPasses\SmokeTestConnectivityCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SmartboxIntegrationFrameworkBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new EventDeferringCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new SmokeTestConnectivityCompilerPass());
        $container->addCompilerPass(new ExpressionLanguageCachePass());

        if ('test' == $container->getParameter('kernel.environment')) {
            $container->addCompilerPass(new MockWebserviceClientsCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
        }
    }
}
