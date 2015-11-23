<?php


namespace Smartbox\Integration\FrameworkBundle\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ItinerariesRouterMatchCommand extends AbstractInternalRouterMatchCommand
{
    public function getInternalRouterName(){
        return 'itineraries';
    }
}
