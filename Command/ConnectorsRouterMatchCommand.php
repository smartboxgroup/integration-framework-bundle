<?php


namespace Smartbox\Integration\FrameworkBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\RouterMatchCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectorsRouterMatchCommand extends AbstractInternalRouterMatchCommand
{
    public function getInternalRouterName(){
        return 'connectors';
    }
}
