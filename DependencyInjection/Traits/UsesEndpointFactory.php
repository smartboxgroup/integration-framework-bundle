<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;

/**
 * Trait UsesEndpointFactory.
 */
trait UsesEndpointFactory
{
    /** @var  EndpointFactory */
    protected $endpointFactory;

    /**
     * @return EndpointFactory
     */
    public function getEndpointFactory()
    {
        return $this->endpointFactory;
    }

    /**
     * @param EndpointFactory $endpointFactory
     */
    public function setEndpointFactory($endpointFactory)
    {
        $this->endpointFactory = $endpointFactory;
    }
}
