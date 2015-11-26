<?php

namespace Smartbox\Integration\FrameworkBundle\Command;


use Smartbox\Integration\FrameworkBundle\Connectors\ConnectorInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Processors\Endpoint;
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

        // CHECK CONNECTOR ROUTES
        $output->writeln("Validating routes...");
        $routerConnectors = $this->getContainer()->get('smartesb.router.connectors');
        foreach($routerConnectors->getRouteCollection()->all() as $name => $route){
            $options = $route->getDefaults();
            if(!array_key_exists(InternalRouter::KEY_CONNECTOR,$options)){
                $output->writeln("<error>Connector not defined for route '$name': ".$route->getPath()."</error>");
                continue;
            }

            $connectorId = str_replace('@','',$options[InternalRouter::KEY_CONNECTOR]);

            if(!$this->getContainer()->has($connectorId)){
                $output->writeln("<error>Connector '$connectorId' not found for route '$name'</error>");
                continue;
            }

            $connector = $this->getContainer()->get($connectorId);

            if(!$connector instanceof ConnectorInterface){
                $output->writeln("<error>Connector '$connectorId' does not implement ConnectorInterface</error>");
                continue;
            }

            $routerConnectors->resolveServices($options);

            $options = array_merge($connector->getDefaultOptions(),$options);

            try {
                $connector->validateOptions($options,false);
            }catch (InvalidOptionException $exception){
                $output->writeln("<error>The route '$name' has an invalid option '".$exception->getFieldName()."' for connector ".$exception->getConnectorClass()." with message ".$exception->getMessage());
                continue;
            }
        }


        // CHECK CONNECTOR ROUTES URIs
        $output->writeln("Validating endpoints...");
        $endpointRepo = $this->getContainer()->get('smartesb.registry.endpoints');
        foreach($endpointRepo->getRegisteredEndpointsUris() as $uri){

            $uri = preg_replace("/{[^{}]+}/",'xxx',$uri);

            try{
                $options = $routerConnectors->match($uri);
            }catch (ResourceNotFoundException $exception){
                $output->writeln("<error>Route not found for URI: '$uri'</error>");
                continue;
            }

            if(!array_key_exists(InternalRouter::KEY_CONNECTOR,$options)){
                $output->writeln("<error>Connector not defined for URI '$uri'</error>");
                continue;
            }

            $connector = $options[InternalRouter::KEY_CONNECTOR];

            if(!$connector instanceof ConnectorInterface){
                $output->writeln("<error>Connector '$connectorId' does not implement ConnectorInterface</error>");
                continue;
            }

            $options = array_merge($connector->getDefaultOptions(),$options);

            try {
                $connector->validateOptions($options,true);
            }catch (InvalidOptionException $exception){
                $output->writeln("<error>The URI: '$uri', has an invalid option ".$exception->getFieldName()." for connector ".$exception->getConnectorClass()." with message ".$exception->getMessage());
                continue;
            }catch (\Exception $exception){
                $output->writeln("<error>Error trying to validate options for URI '$uri', ".$exception->getMessage());
            }
        }

        $output->writeln("Validation finished");
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('smartesb:validate')
            ->setDefinition(array())
            ->setDescription('Validates connector routes and endpoint URIs')
        ;
    }
}