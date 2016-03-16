<?php

namespace Smartbox\Integration\FrameworkBundle;

use Smartbox\Integration\FrameworkBundle\DependencyInjection\EventDeferringCompilerPass;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\MockWebserviceClientsCompilerPass;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\SmokeTestConnectivityCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SmartboxIntegrationFrameworkBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new EventDeferringCompilerPass(),PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new SmokeTestConnectivityCompilerPass());

        if($container->getParameter('kernel.environment') == 'test'){
            $container->addCompilerPass(new MockWebserviceClientsCompilerPass(),PassConfig::TYPE_AFTER_REMOVING);
        }
    }
}
