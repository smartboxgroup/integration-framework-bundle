<?php


namespace Smartbox\Integration\FrameworkBundle\Command;

class EndpointsRouterDebugCommand extends AbstractInternalRouterDebugCommand
{
    public function getInternalRouterName(){
        return 'endpoints';
    }
}
