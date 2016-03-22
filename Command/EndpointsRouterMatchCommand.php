<?php


namespace Smartbox\Integration\FrameworkBundle\Command;

class EndpointsRouterMatchCommand extends AbstractInternalRouterMatchCommand
{
    public function getInternalRouterName(){
        return 'endpoints';
    }
}
