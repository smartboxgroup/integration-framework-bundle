<?php

namespace Smartbox\Integration\FrameworkBundle\Helper;

/**
 * Class EndpointsRegistry
 * @deprecated Do not use this!!
 * @package Smartbox\Integration\FrameworkBundle\Helper
 */
class EndpointsRegistry
{
    /** @var array */
    protected $endpoints = [];

    /**
     * @param $endpointId
     */
    public function register($endpointId, $uri)
    {
        $this->endpoints[$uri] = $endpointId;
    }

    public function getEndpoint(){

    }

    /**
     * @return array
     */
    public function getRegisteredEndpoints()
    {
        return $this->endpoints;
    }

    /**
     * @return array
     */
    public function getRegisteredEndpointsUris()
    {
        return array_keys($this->endpoints);
    }

    /**
     * @return array
     */
    public function getRegisteredEndpointsIds()
    {
        return array_values($this->endpoints);
    }
}
