<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\CompilerPasses;

use BeSimple\SoapClient\Curl;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class MockWebserviceClientsCompilerPass.
 */
class MockWebserviceClientsCompilerPass implements CompilerPassInterface
{
    const TAG_MOCKABLE_SOAP_CLIENT = 'mockable.soap_client';
    const TAG_MOCKABLE_REST_CLIENT = 'mockable.rest_client';
    const TAG_ATTR_MOCK_LOCATION = 'mockLocation';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $soapClientIds = $container->findTaggedServiceIds(self::TAG_MOCKABLE_SOAP_CLIENT);

        foreach ($soapClientIds as $id => $parameters) {
            $mockLocation = $parameters[0][self::TAG_ATTR_MOCK_LOCATION];
            $serviceDef = $container->getDefinition($id);
            $serviceDef->setClass($container->getParameter('fake_soap_client.class'));
            $serviceDef->addMethodCall('init', [new Reference('file_locator'), $mockLocation, []]);
            $soapClientOptions = $serviceDef->getArgument(1);
            $soapClientOptions['MockCacheDir'] = $mockLocation;
            if( array_key_exists('login', $soapClientOptions) && array_key_exists('password',$soapClientOptions)){
                if(!array_key_exists('auth_type',$soapClientOptions) || $soapClientOptions['auth_type'] !== Curl::AUTH_TYPE_NTLM ) {
                    $soapClientOptions['auth_type'] = Curl::AUTH_TYPE_BASIC;
                }
            }
            $serviceDef->addMethodCall('setAuthType', [$soapClientOptions['auth_type'] ?? Curl::AUTH_TYPE_NONE]);
            $serviceDef->replaceArgument(1,$soapClientOptions);
        }

//        foreach ($container->getDefinitions() as $id => $definition) {
//            $definition->setPublic(true);
//        }
//
//        foreach ($container->getAliases() as $id => $alias) {
//            $alias->setPublic(true);
//        }

        $restProducerIds = $container->findTaggedServiceIds(self::TAG_MOCKABLE_REST_CLIENT);

        foreach ($restProducerIds as $id => $parameters) {
            $mockLocation = $parameters[0][self::TAG_ATTR_MOCK_LOCATION];
            $serviceDef = $container->getDefinition($id);
            $serviceDef->setClass($container->getParameter('fake_rest_client.class'));
            $serviceDef->addMethodCall('init', [new Reference('file_locator'), $mockLocation, []]);
        }
    }
}
