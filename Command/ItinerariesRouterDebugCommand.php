<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

/**
 * Class ItinerariesRouterDebugCommand.
 */
class ItinerariesRouterDebugCommand extends AbstractInternalRouterDebugCommand
{
    public function getInternalRouterName()
    {
        return 'itineraries';
    }
}
