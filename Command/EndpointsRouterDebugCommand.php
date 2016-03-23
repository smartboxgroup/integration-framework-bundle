<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

/**
 * Class EndpointsRouterDebugCommand.
 */
class EndpointsRouterDebugCommand extends AbstractInternalRouterDebugCommand
{
    public function getInternalRouterName()
    {
        return 'endpoints';
    }
}
