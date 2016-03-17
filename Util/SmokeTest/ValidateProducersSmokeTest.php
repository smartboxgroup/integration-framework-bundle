<?php

namespace Smartbox\Integration\FrameworkBundle\Util\SmokeTest;

use Smartbox\CoreBundle\Utils\SmokeTest\SmokeTestInterface;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;
use Smartbox\Integration\FrameworkBundle\Producers\ProducerInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouteCollection;

class ValidateProducersSmokeTest implements SmokeTestInterface
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
        return 'SmokeTest for producers validation.';
    }

    public function run()
    {
        $smokeTestOutput = new SmokeTestOutput();
        $exitCode = SmokeTestOutput::OUTPUT_CODE_SUCCESS;

        // CHECK producer ROUTES

        /** @var InternalRouter $routerProducers */
        $routerProducers = $this->getContainer()->get('smartesb.router.producers');
        foreach($routerProducers->getRouteCollection()->all() as $name => $route){
            $options = $route->getDefaults();
            if(!array_key_exists(InternalRouter::KEY_producer,$options)){
                $smokeTestOutput->addMessage("Producer not defined for route '$name': ".$route->getPath());
                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }

            $producerId = str_replace('@','',$options[InternalRouter::KEY_producer]);

            if(!$this->getContainer()->has($producerId)){
                $smokeTestOutput->addMessage("Producer '$producerId' not found for route '$name'");

                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }

            $producer = $this->getContainer()->get($producerId);

            if(!$producer instanceof ProducerInterface){
                $smokeTestOutput->addMessage("Producer '$producerId' does not implement ProducerInterface");

                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }

            $routerProducers->resolveServices($options);

            $options = array_merge($producer->getDefaultOptions(),$options);

            try {
                $producer->validateOptions($options,false);
            }catch (InvalidOptionException $exception){
                $smokeTestOutput->addMessage("The route '$name' has an invalid option '".$exception->getFieldName()."' for producer ".$exception->getProducerClass()." with message ".$exception->getMessage());

                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }
        }

        // CHECK producer ROUTES URIs

        $producerRoutes = $this->getContainer()->get('smartesb.router.producers');
        /** @var RouteCollection $collection */
        $collection = $producerRoutes->getRouteCollection();
        foreach($collection->all() as $route){
            $uri = substr($route->getPath(),1);
            $uri = preg_replace("/{[^{}]+}/",'xxx',$uri);

            try{
                $options = $routerProducers->match($uri);
            }catch (ResourceNotFoundException $exception){
                $smokeTestOutput->addMessage("Route not found for URI: '$uri'");

                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }

            if(!array_key_exists(InternalRouter::KEY_producer,$options)){
                $smokeTestOutput->addMessage("Producer not defined for URI '$uri'");

                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }

            $producer = $options[InternalRouter::KEY_producer];

            if (!$producer instanceof ProducerInterface) {
                $smokeTestOutput->addMessage("Producer '$producer' does not implement ProducerInterface");

                $exitCode = SmokeTestOutput::OUTPUT_CODE_FAILURE;
                continue;
            }

            $options = array_merge($producer->getDefaultOptions(),$options);

            try {
                $producer->validateOptions($options,true);
            } catch (InvalidOptionException $exception) {
                $smokeTestOutput->addMessage("The URI: '$uri', has an invalid option ".$exception->getFieldName()." for producer ".$exception->getProducerClass()." with message ".$exception->getMessage());

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