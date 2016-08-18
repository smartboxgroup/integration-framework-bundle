<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Processors\EndpointProcessor;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\ProtocolInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ValidateContainerCommand extends ContainerAwareCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exitCode = 0;

        // CHECK producer ROUTES
        $output->writeln('<info>Validating routes...</info>');
        $routerEndpoints = $this->getContainer()->get('smartesb.router.endpoints');
        foreach ($routerEndpoints->getRouteCollection()->all() as $name => $route) {
            $cleanPath = $route->getPath();
            if ($cleanPath[0] == '/') {
                $cleanPath = substr($cleanPath, 1);
            }

            $options = $route->getDefaults();
            $routerEndpoints->resolveServices($options);
            $optionsResolver = new OptionsResolver();

            // Check protocol
            if (!array_key_exists(Protocol::OPTION_PROTOCOL, $options)) {
                $output->writeln("<error>Protocol not defined for route '$name': $cleanPath </error>");
                $exitCode = 1;
                continue;
            }

            $protocol = $options[Protocol::OPTION_PROTOCOL];

            if (!$protocol instanceof ProtocolInterface) {
                $output->writeln("<error>Protocol '".get_class($protocol)."' found in route '$name' does not implement ProducerInterface</error>");

                $exitCode = 1;
                continue;
            }

            $protocol->configureOptionsResolver($optionsResolver);

            // Check producer, consumer, handler
            $producer = array_key_exists(Protocol::OPTION_PRODUCER, $options) ? $options[Protocol::OPTION_PRODUCER] : $protocol->getDefaultProducer();
            $consumer = array_key_exists(Protocol::OPTION_CONSUMER, $options) ? $options[Protocol::OPTION_CONSUMER] : $protocol->getDefaultConsumer();
            $handler = array_key_exists(Protocol::OPTION_HANDLER, $options) ? $options[Protocol::OPTION_HANDLER] : $protocol->getDefaultHandler();

            if (!empty($producer)) {
                if (!$producer instanceof ProducerInterface) {
                    $output->writeln('<error>Producer of class '.get_class($producer)." found in route: '$name' does not implement ProducerInterface</error>");
                    $exitCode = 1;
                    continue;
                }

                if ($producer instanceof ConfigurableInterface) {
                    $producer->configureOptionsResolver($optionsResolver);
                }
            }

            if (!empty($consumer)) {
                if (!$consumer instanceof ConsumerInterface) {
                    $output->writeln('<error>Consumer of class '.get_class($consumer)." found in route: '$name' does not implement ConsumerInterface</error>");
                    $exitCode = 1;
                    continue;
                }

                if ($consumer instanceof ConfigurableInterface) {
                    $consumer->configureOptionsResolver($optionsResolver);
                }
            }

            if (!empty($handler)) {
                if (!$handler instanceof HandlerInterface) {
                    $output->writeln('<error>Handler of class '.get_class($handler)." found in route: '$name' does not implement HandlerInterface</error>");
                    $exitCode = 1;
                    continue;
                }

                if ($handler instanceof ConfigurableInterface) {
                    $handler->configureOptionsResolver($optionsResolver);
                }
            }

            // Remove undesired options
            unset($options[Protocol::OPTION_PROTOCOL]);
            unset($options[Protocol::OPTION_PRODUCER]);
            unset($options[Protocol::OPTION_CONSUMER]);
            unset($options[Protocol::OPTION_HANDLER]);

            // Don't check options which should by defined in the route somewhere
            $missingRequirements = array_diff(array_keys($route->getRequirements()), array_keys($options));
            $requirements = $missingRequirements;
            $optionsResolver->remove($requirements);

            try {
                $optionsResolver->resolve($options);
            } catch (\Exception $exception) {
                $output->writeln("<error>The route '$name' has an problem in its options. ".$exception->getMessage().'</error>');

                $exitCode = 1;
                continue;
            }

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln("<info>Checked route $name: ".$cleanPath.'</info>');
            }
        }

        // CHECK producer ROUTES URIs
        $output->writeln('<info>Validating endpoints...</info>');
        $itinerariesRepo = $this->getContainer()->get('smartesb.map.itineraries');

        foreach ($itinerariesRepo->getItineraries() as $itineraryId) {
            /** @var Itinerary $itinerary */
            $itinerary = $this->getContainer()->get($itineraryId);
            foreach ($itinerary->getProcessorIds() as $processorId) {
                $processor = $this->getContainer()->get($processorId);
                if ($processor instanceof EndpointProcessor) {
                    $uri = $processor->getURI();
                    if (!$this->checkEndpointURI($uri, $output)) {
                        $exitCode = 1;
                    }
                }
            }
        }

        $output->writeln('<info>Validation finished</info>');

        return $exitCode;
    }

    protected function checkEndpointURI($uri, OutputInterface $output)
    {
        $endpointFactory = $this->getContainer()->get('smartesb.endpoint_factory');
        $uri = preg_replace('/{[^{}]+}/', 'xxx', $uri);

        try {
            $endpoint = $endpointFactory->createEndpoint($uri, EndpointFactory::MODE_CONSUME);
        } catch (\Exception $exception) {
            $output->writeln("<error>Problem detected for URI: '$uri'</error>");

            return false;
        }

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            $output->writeln("<info>Checked endpoint URI: $uri</info>");
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
