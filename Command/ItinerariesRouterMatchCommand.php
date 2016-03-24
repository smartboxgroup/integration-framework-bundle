<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

/**
 * Class ItinerariesRouterMatchCommand.
 */
class ItinerariesRouterMatchCommand extends AbstractInternalRouterMatchCommand
{
    public function getInternalRouterName()
    {
        return 'itineraries';
    }
}
