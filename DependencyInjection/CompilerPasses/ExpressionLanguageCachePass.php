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
        // Last line (the reference to cache.app) triggers a "Constructing service "monolog.logger.cache" from a parent definition is not supported at build time."
        // By bypassing this pass the container builds, but expression language throws a deprecation notice about not passing a cache interface in the constructor
        return;

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
        $container->findDefinition('smartesb.util.expression_language')->setPublic(true);
    }
}
