<?php

namespace Smartbox\Integration\FrameworkBundle\Command;


use Smartbox\Integration\FrameworkBundle\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\Producers\ProducerInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Processors\EndpointProcessor;
use Smartbox\Integration\FrameworkBundle\Processors\Itinerary;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\TraceableUrlMatcher;
use Symfony\Component\Routing\RouterInterface;

class ValidateContainerCommand extends ContainerAwareCommand {

    protected function execute(InputInterface $input, OutputInterface $output){

        $exitCode = 0;

        // CHECK producer ROUTES
        $output->writeln("Validating routes...");
        $routerProducers = $this->getContainer()->get('smartesb.router.endpoints');
        foreach($routerProducers->getRouteCollection()->all() as $name => $route){
            $options = $route->getDefaults();
            if(!array_key_exists(InternalRouter::KEY_producer,$options)){
                $output->writeln("<error>Producer not defined for route '$name': ".$route->getPath()."</error>");
                $exitCode = 1;
                continue;
            }

            $producerId = str_replace('@','',$options[InternalRouter::KEY_producer]);

            if(!$this->getContainer()->has($producerId)){
                $output->writeln("<error>Producer '$producerId' not found for route '$name'</error>");

                $exitCode = 1;
                continue;
            }

            $producer = $this->getContainer()->get($producerId);

            if(!$producer instanceof ProducerInterface){
                $output->writeln("<error>Producer '$producerId' does not implement ProducerInterface</error>");

                $exitCode = 1;
                continue;
            }

            $routerProducers->resolveServices($options);

            $options = array_merge($producer->getDefaultOptions(),$options);

            try {
                $producer->validateOptions($options,false);
            }catch (InvalidOptionException $exception){
                $output->writeln("<error>The route '$name' has an invalid option '".$exception->getOptionName()."' for producer ".$exception->getClassName()." with message ".$exception->getMessage());

                $exitCode = 1;
                continue;
            }
        }

        // CHECK producer ROUTES URIs
        $output->writeln("Validating endpoints...");
        $itinerariesRepo = $this->getContainer()->get('smartesb.map.itineraries');

        foreach($itinerariesRepo->getItineraries() as $itineraryId){
            /** @var Itinerary $itinerary */
            $itinerary = $this->getContainer()->get($itineraryId);
            foreach($itinerary->getProcessors() as $processor){
                if($processor instanceof EndpointProcessor){
                    $uri = $processor->getURI();
                    if(!$this->checkEndpointURI($uri,$output)){
                        $exitCode = 1;
                    }
                }
            }
        }

        $output->writeln("Validation finished");
        return $exitCode;
    }

    protected function checkEndpointURI($uri, OutputInterface $output){
        $routerProducers = $this->getContainer()->get('smartesb.router.endpoints');
        $uri = preg_replace("/{[^{}]+}/",'xxx',$uri);

        try{
            $options = $routerProducers->match($uri);
        }catch (ResourceNotFoundException $exception){
            $output->writeln("<error>Route not found for URI: '$uri'</error>");
            return false;
        }

        if(!array_key_exists(InternalRouter::KEY_producer,$options)){
            $output->writeln("<error>Producer not defined for URI '$uri'</error>");
            return false;
        }

        /** @var Producer $producer */
        $producer = $options[InternalRouter::KEY_producer];

        if(!$producer instanceof ProducerInterface){
            $output->writeln("<error>Producer '".$producer->getId()."' does not implement ProducerInterface</error>");
            return false;
        }

        $options = array_merge($producer->getDefaultOptions(),$options);

        try {
            $producer->validateOptions($options,true);
        }catch (InvalidOptionException $exception){
            $output->writeln("<error>The URI: '$uri', has an invalid option ".$exception->getOptionName()." for producer ".$exception->getClassName()." with message ".$exception->getMessage());
            return false;
        }catch (\Exception $exception){
            $output->writeln("<error>Error trying to validate options for URI '$uri', ".$exception->getMessage());
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('smartesb:validate')
            ->setDefinition(array())
            ->setDescription('Validates producer routes and endpoint URIs')
        ;
    }
}