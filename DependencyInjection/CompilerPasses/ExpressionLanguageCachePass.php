<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\CompilerPasses;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;

class ExpressionLanguageCachePass implements CompilerPassInterface
{
    const NEEDED_SERVICES = ['cache.app', 'smartesb.util.expression_language'];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach (static::NEEDED_SERVICES as $id) {
            if (!$container->has($id)) {
                return;
            }
        }

        // Only supported starting from Symfony 3.2
        if (version_compare(Kernel::VERSION, '3.2.0', '<')) {
            return;
        }

        $container->findDefinition('smartesb.util.expression_language')->addArgument(new Reference('cache.app'));
    }
}
