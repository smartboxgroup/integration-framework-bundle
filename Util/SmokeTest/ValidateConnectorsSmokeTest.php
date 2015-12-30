<?php

namespace Smartbox\Integration\FrameworkBundle\Util\SmokeTest;

use Smartbox\CoreBundle\Utils\SmokeTest\SmokeTestInterface;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;
use Smartbox\Integration\FrameworkBundle\Connectors\ConnectorInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Helper\EndpointsRegistry;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class ValidateConnectorsSmokeTest implements SmokeTestInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function getContainer()
    {
        return $this->container;
    }

    public function getDescription()
    {
        return 'SmokeTest for connectors validation.';
    }

    public function run()
    {
        $smokeTestOutput = new SmokeTestOutput();
        $exitCode = SmokeTestOutput::OUTPUT_CODE_SUCCESS;

        // CHECK CONNECTOR ROUTES

        /** @var InternalRouter $routerConnectors */
        $routerConnectors = $this->getContainer()->get('smartesb.router.connectors');
        foreach($routerConnectors->getRouteCollection()->all() as $name => $route){
            $options = $route->getDefaults();
            if(!array_key_exists(InternalRouter::KEY_CONNECTOR,$options)){
                $smokeTestOutput->addMessage("Connector not defined for route '$name': ".$route->getPath());
                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }

            $connectorId = str_replace('@','',$options[InternalRouter::KEY_CONNECTOR]);

            if(!$this->getContainer()->has($connectorId)){
                $smokeTestOutput->addMessage("Connector '$connectorId' not found for route '$name'");

                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }

            $connector = $this->getContainer()->get($connectorId);

            if(!$connector instanceof ConnectorInterface){
                $smokeTestOutput->addMessage("Connector '$connectorId' does not implement ConnectorInterface");

                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }

            $routerConnectors->resolveServices($options);

            $options = array_merge($connector->getDefaultOptions(),$options);

            try {
                $connector->validateOptions($options,false);
            }catch (InvalidOptionException $exception){
                $smokeTestOutput->addMessage("The route '$name' has an invalid option '".$exception->getFieldName()."' for connector ".$exception->getConnectorClass()." with message ".$exception->getMessage());

                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }
        }

        // CHECK CONNECTOR ROUTES URIs

        /** @var EndpointsRegistry $endpointRepo */
        $endpointRepo = $this->getContainer()->get('smartesb.registry.endpoints');
        foreach($endpointRepo->getRegisteredEndpointsUris() as $uri){

            $uri = preg_replace("/{[^{}]+}/",'xxx',$uri);

            try{
                $options = $routerConnectors->match($uri);
            }catch (ResourceNotFoundException $exception){
                $smokeTestOutput->addMessage("Route not found for URI: '$uri'");

                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }

            if(!array_key_exists(InternalRouter::KEY_CONNECTOR,$options)){
                $smokeTestOutput->addMessage("Connector not defined for URI '$uri'");

                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }

            $connector = $options[InternalRouter::KEY_CONNECTOR];

            if (!$connector instanceof ConnectorInterface) {
                $smokeTestOutput->addMessage("Connector '$connector' does not implement ConnectorInterface");

                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }

            $options = array_merge($connector->getDefaultOptions(),$options);

            try {
                $connector->validateOptions($options,true);
            } catch (InvalidOptionException $exception) {
                $smokeTestOutput->addMessage("The URI: '$uri', has an invalid option ".$exception->getFieldName()." for connector ".$exception->getConnectorClass()." with message ".$exception->getMessage());

                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            } catch (\Exception $exception) {
                $smokeTestOutput->addMessage("Error trying to validate options for URI '$uri', ".$exception->getMessage());
                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
            }
        }

        if ($exitCode === SmokeTestOutput::OUTPUT_CODE_SUCCESS) {
            $smokeTestOutput->addMessage('Validation finished');
        }

        $smokeTestOutput->setCode($exitCode);

        return $smokeTestOutput;
    }
}