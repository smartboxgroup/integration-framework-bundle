<?php


namespace Smartbox\Integration\FrameworkBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\RouterDebugCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProducersRouterDebugCommand extends AbstractInternalRouterDebugCommand
{
    public function getInternalRouterName(){
        return 'producers';
    }
}
