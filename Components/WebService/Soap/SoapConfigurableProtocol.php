<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Soap;

use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;
use Smartbox\Integration\FrameworkBundle\Configurability\DescriptableInterface;

class SoapConfigurableProtocol extends ConfigurableWebserviceProtocol implements DescriptableInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Configurable protocol to integrate with SOAP-based web services';
    }
}
