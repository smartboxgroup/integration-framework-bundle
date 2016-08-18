<?php

namespace Smartbox\Integration\FrameworkBundle\Components\SymfonyService;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Producers\Producer;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class QueueProducer.
 */
class SymfonyServiceProducer extends Producer implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function send(Exchange $ex, EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();

        $serviceId = $options[SymfonyServiceProtocol::OPTION_SERVICE];
        $serviceMethod = $options[SymfonyServiceProtocol::OPTION_METHOD];

        if (!$this->container->has($serviceId)) {
            throw new \RuntimeException("Service id \"$serviceId\" not found while trying to send a message to endpoint with URI: ".$endpoint->getURI());
        }

        $service = $this->container->get($serviceId);

        if (!method_exists($service, $serviceMethod)) {
            throw new \RuntimeException("Method \"$serviceMethod\" not defined in service: \"$serviceId\" while trying to send a message to endpoint with URI: ".$endpoint->getURI());
        }

        $service->$serviceMethod($ex);
    }
}
