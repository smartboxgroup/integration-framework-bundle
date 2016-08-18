<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;

/**
 * Trait UsesItinerariesRouter.
 */
trait UsesItinerariesRouter
{
    /** @var InternalRouter */
    protected $itinerariesRouter;

    /**
     * @return InternalRouter
     */
    public function getItinerariesRouter()
    {
        return $this->itinerariesRouter;
    }

    /**
     * @param InternalRouter $itinerariesRouter
     */
    public function setItinerariesRouter(InternalRouter $itinerariesRouter)
    {
        $this->itinerariesRouter = $itinerariesRouter;
    }
}
