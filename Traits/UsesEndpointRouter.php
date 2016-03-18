<?php

namespace Smartbox\Integration\FrameworkBundle\Traits;

use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;

trait UsesEndpointRouter {

    /**
     * @var InternalRouter
     */
    protected $endpointsRouter;

    /**
     * @return InternalRouter
     */
    public function getEndpointsRouter()
    {
        return $this->endpointsRouter;
    }

    /**
     * @param InternalRouter $endpointsRouter
     */
    public function setEndpointsRouter($endpointsRouter)
    {
        $this->endpointsRouter = $endpointsRouter;
    }
}