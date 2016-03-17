<?php
namespace Smartbox\Integration\FrameworkBundle\DependencyInjection;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MockWebserviceClientsCompilerPass implements CompilerPassInterface
{
    const TAG_MOCKABLE_SOAP_CLIENT = 'mockable.soap_client';
    const TAG_MOCKABLE_REST_CLIENT = 'mockable.rest_client';
    const TAG_ATTR_MOCK_LOCATION = 'mockLocation';

    public function process(ContainerBuilder $container){
        $soapProducerIds = $container->findTaggedServiceIds(self::TAG_MOCKABLE_SOAP_CLIENT);

        foreach($soapProducerIds as $id => $parameters){
            $mockLocation = $parameters[0][self::TAG_ATTR_MOCK_LOCATION];
            $serviceDef = $container->getDefinition($id);
            $serviceDef->setClass($container->getParameter('fake_soap_client.class'));
            $serviceDef->addMethodCall('init',[new Reference('file_locator'),$mockLocation,[]]);
        }

        $restProducerIds = $container->findTaggedServiceIds(self::TAG_MOCKABLE_REST_CLIENT);

        foreach($restProducerIds as $id => $parameters){
            $mockLocation = $parameters[0][self::TAG_ATTR_MOCK_LOCATION];
            $serviceDef = $container->getDefinition($id);
            $serviceDef->setClass($container->getParameter('fake_rest_client.class'));
            $serviceDef->addMethodCall('init',[new Reference('file_locator'),$mockLocation,[]]);
        }
    }
}