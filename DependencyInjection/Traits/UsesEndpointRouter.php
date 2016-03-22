<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;

/**
 * Trait UsesEndpointRouter.
 */
trait UsesEndpointRouter
{
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
