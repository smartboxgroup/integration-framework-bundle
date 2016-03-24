<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

/**
 * Class EndpointsRouterMatchCommand.
 */
class EndpointsRouterMatchCommand extends AbstractInternalRouterMatchCommand
{
    public function getInternalRouterName()
    {
        return 'endpoints';
    }
}
