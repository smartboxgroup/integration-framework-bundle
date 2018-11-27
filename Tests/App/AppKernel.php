<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle(),
            new \BeSimple\SoapBundle\BeSimpleSoapBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),

            new \Smartbox\CoreBundle\SmartboxCoreBundle(),
            new \Smartbox\Integration\FrameworkBundle\SmartboxIntegrationFrameworkBundle(),
            new \Smartbox\Integration\CamelConfigBundle\SmartboxIntegrationCamelConfigBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');
    }
}
