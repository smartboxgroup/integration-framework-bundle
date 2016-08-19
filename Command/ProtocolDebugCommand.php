<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Configurability\DescriptableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\ProtocolInterface;
use Smartbox\Integration\FrameworkBundle\Service;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class ProtocolsInfoCommand.
 */
class ProtocolDebugCommand extends ContainerAwareCommand
{
    const ARGUMENT_PROTOCOL_ID = 'protocol_id';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('smartesb:debug:protocols')
            ->setDefinition([
                new InputArgument(self::ARGUMENT_PROTOCOL_ID, InputArgument::OPTIONAL, 'A protocol id'),
            ])
            ->setDescription('Display information about the available protocols and the related routes')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $protocolId = $input->getArgument(self::ARGUMENT_PROTOCOL_ID);

        $protocols = $this->retrieveProtocols((bool) $protocolId);

        if (!$protocolId) {
            $protocolsTable = [];

            foreach ($protocols as $id => $protocol) {
                $protocolsTable[] = [
                    $id,
                    $protocol['description'],
                ];
            }

            // Prints the list of protocols available
            $formatter = new FormatterHelper();
            $header = $formatter->formatSection(
                'Protocols',
                'List of available protocols'
            );
            $output->writeln($header);
            $table = new Table($output);
            array_unshift($protocolsTable, ['<info>Id</info>', '<info>Description</info>'], new TableSeparator());
            $table->setRows($protocolsTable);
            $table->setStyle('compact');
            $table->render();
        } else {
            // Prints all the specific details of a protocol
            if (!isset($protocols[$protocolId])) {
                throw new \InvalidArgumentException(sprintf('Invalid protocol "%s"', $protocolId));
            }

            $this->renderProtocolInfo($protocolId, $protocols[$protocolId], $output);
        }
    }

    protected function retrieveProtocols($detailed = true)
    {
        $protocols = [];

        $router = $this->getRouter();
        $routes = $router->getRouteCollection()->all();

        foreach ($routes as $route) {
            /**
             * @var string
             * @var ProtocolInterface|DescriptableInterface $currentProtocol
             */
            list($currentProtocolId, $currentProtocol) = $this->getProtocolForRoute($route);
            $description = $currentProtocol instanceof DescriptableInterface ? $currentProtocol->getDescription() : '';

            if ($detailed) {
                // gather here more details about the protocols
                if (!isset($protocols[$currentProtocolId])) {
                    $protocols[$currentProtocolId] = [
                        'class' => get_class($currentProtocol),
                        'description' => $description,
                        'options' => $currentProtocol->getOptionsDescriptions(),
                        'consumers' => [],
                        'producers' => [],
                        'handlers' => [],
                    ];
                }

                /** @var ConsumerInterface|ConfigurableInterface|Service $consumer */
                $consumer = $this->getConsumerForRoute($route, $currentProtocol);
                if ($consumer) {
                    $consumerId = $this->getIdFromService($consumer, $currentProtocolId);
                    $consumerClass = get_class($consumer);
                    if (!array_key_exists($consumerClass, $protocols[$currentProtocolId]['consumers'])) {
                        $protocols[$currentProtocolId]['consumers'][$consumerId] = [
                            'class' => $consumerClass,
                            'routes' => [],
                            'options' => [],
                        ];

                        if ($consumer instanceof ConfigurableInterface) {
                            $protocols[$currentProtocolId]['consumers'][$consumerId]['options'] =
                                $consumer->getOptionsDescriptions();
                        }
                    }
                    $protocols[$currentProtocolId]['consumers'][$consumerId]['routes'][] = $route->getPath();
                }

                /** @var ProducerInterface|ConfigurableInterface|Service $producer */
                $producer = $this->getProducerForRoute($route, $currentProtocol);
                if ($producer) {
                    $producerId = $this->getIdFromService($producer, $currentProtocolId);
                    $producerClass = get_class($producer);
                    if (!array_key_exists($producerClass, $protocols[$currentProtocolId]['producers'])) {
                        $protocols[$currentProtocolId]['producers'][$producerId] = [
                            'class' => $producerClass,
                            'routes' => [],
                            'options' => [],
                        ];

                        if ($producer instanceof ConfigurableInterface) {
                            $protocols[$currentProtocolId]['producers'][$producerId]['options'] =
                                $producer->getOptionsDescriptions();
                        }
                    }
                    $protocols[$currentProtocolId]['producers'][$producerId]['routes'][] = $route->getPath();
                }

                /** @var HandlerInterface|ConfigurableInterface|Service $handler */
                $handler = $this->getHandlerForRoute($route, $currentProtocol);
                if ($handler) {
                    $handlerId = $this->getIdFromService($handler, $currentProtocolId);
                    $handlerClass = get_class($handler);
                    if (!array_key_exists($handlerClass, $protocols[$currentProtocolId]['handlers'])) {
                        $protocols[$currentProtocolId]['handlers'][$handlerId] = [
                            'class' => $handlerClass,
                            'routes' => [],
                            'options' => [],
                        ];

                        if ($handler instanceof ConfigurableInterface) {
                            $protocols[$currentProtocolId]['handlers'][$handlerId]['options'] =
                                $handler->getOptionsDescriptions();
                        }
                        $protocols[$currentProtocolId]['handlers'][$handlerId]['routes'][] = $route->getPath();
                    }
                }
            } else { // if not detailed
                $protocols[$currentProtocolId] = [
                    'class' => get_class($currentProtocol),
                    'description' => $description,
                ];
            }
        }

        return $protocols;
    }

    protected function renderProtocolInfo($protocolId, array $p, OutputInterface $output)
    {
        $output->writeln('');
        $formatter = new FormatterHelper();
        $header = $formatter->formatSection('Protocol', '');
        $output->writeln($header);
        $table = new Table($output);
        $table->setRows([
            ['<comment>Id</comment>', $protocolId],
            ['<comment>Class</comment>', $p['class']],
            ['<comment>Description</comment>', $p['description']],
        ]);
        $table->setStyle('compact');
        $table->render();

        // OPTIONS
        if (!empty($p['options'])) {
            $output->writeln('');
            $output->writeln('');
            $optionsHeader = $formatter->formatSection('Options', '');
            $output->writeln($optionsHeader);

            foreach ($p['options'] as $optionName => $option) {
                $output->writeln('');
                $output->writeln(" <comment>$optionName</comment>:");
                $output->writeln("   $option[0]");
                if (!empty($option[1])) {
                    $output->writeln('   Accepted values:');
                    foreach ($option[1] as $value => $valueDescription) {
                        $output->writeln("   - <comment>$value</comment>: $valueDescription");
                    }
                }
            }
        }

        // CONSUMERS
        if (!empty($p['consumers'])) {
            $this->renderSection('Consumers', $p['consumers'], $output, $formatter);
        }

        // PRODUCERS
        if (!empty($p['producers'])) {
            $this->renderSection('Producers', $p['producers'], $output, $formatter);
        }

        // HANDLERS
        if (!empty($p['handlers'])) {
            $this->renderSection('Handlers', $p['handlers'], $output, $formatter);
        }

        $output->writeln('');
    }

    /**
     * Render a specific section for a protocol (used for Consumers, Producers and Handlers).
     *
     * @param                 $sectionName
     * @param                 $sectionData
     * @param OutputInterface $output
     * @param FormatterHelper $formatter
     */
    protected function renderSection($sectionName, $sectionData, OutputInterface $output, FormatterHelper $formatter)
    {
        $output->writeln('');
        $output->writeln('');
        $optionsHeader = $formatter->formatSection($sectionName, '');
        $output->writeln($optionsHeader);

        foreach ($sectionData as $serviceId => $service) {
            $output->writeln('');
            $output->writeln(" - <comment>$serviceId</comment>:");

            // Class
            $output->writeln('');
            $output->writeln("     <comment>Class</comment>: ${service['class']}");

            // Routes
            $output->writeln('');
            $output->writeln('     <comment>Routes</comment>:');
            foreach ($service['routes'] as $consumerRoute) {
                $output->writeln("     - $consumerRoute");
            }

            // Options
            if (!empty($service['options'])) {
                $output->writeln('');
                $output->writeln('     <comment>Options</comment>:');
                foreach ($service['options'] as $consumerOptionName => $consumerOption) {
                    $output->writeln("     - <comment>$consumerOptionName</comment>:");
                    $output->writeln("         $consumerOption[0]");
                    if (!empty($consumerOption[1])) {
                        $output->writeln('         Accepted values:');
                        foreach ($consumerOption[1] as $value => $valueDescription) {
                            $output->writeln("         - <comment>$value</comment>: $valueDescription");
                        }
                    }
                }
            }
        }
    }

    /**
     * @return RouterInterface
     */
    protected function getRouter()
    {
        return $this->getContainer()->get('smartesb.router.endpoints');
    }

    /**
     * @param Route $route
     *
     * @return array an array with two elements:
     *               - a string with the id of the protocol
     *               - the protocol instance
     */
    protected function getProtocolForRoute(Route $route)
    {
        $protocolId = $route->getDefault(Protocol::OPTION_PROTOCOL);
        if (!$protocolId) {
            throw new InvalidConfigurationException(sprintf(
                'Route "%s" has no protocol defined',
                $route->getPath()
            ));
        }

        $protocolId = substr($protocolId, 1);

        return [$protocolId, $this->getContainer()->get($protocolId)];
    }

    /**
     * @param Route             $route
     * @param ProtocolInterface $protocol
     *
     * @return ConsumerInterface
     */
    protected function getConsumerForRoute(Route $route, ProtocolInterface $protocol)
    {
        $consumerId = $route->getDefault(Protocol::OPTION_CONSUMER);
        if ($consumerId) {
            $consumerId = substr($consumerId, 1);

            return $this->getContainer()->get($consumerId);
        } else {
            return $protocol->getDefaultConsumer();
        }
    }

    /**
     * @param Route             $route
     * @param ProtocolInterface $protocol
     *
     * @return ProducerInterface
     */
    protected function getProducerForRoute(Route $route, ProtocolInterface $protocol)
    {
        $producerId = $route->getDefault(Protocol::OPTION_PRODUCER);
        if ($producerId) {
            $producerId = substr($producerId, 1);

            return $this->getContainer()->get($producerId);
        } else {
            return $protocol->getDefaultProducer();
        }
    }

    /**
     * @param Route             $route
     * @param ProtocolInterface $protocol
     *
     * @return HandlerInterface
     */
    protected function getHandlerForRoute(Route $route, ProtocolInterface $protocol)
    {
        $handlerId = $route->getDefault(Protocol::OPTION_HANDLER);
        if ($handlerId) {
            $handlerId = substr($handlerId, 1);

            return $this->getContainer()->get($handlerId);
        } else {
            return $protocol->getDefaultHandler();
        }
    }

    /**
     * Tries to read the id of a specific service found inside a given protocol.
     * If it's not possible to get the id (it was not set) it throws an exception.
     *
     * @param Service $service
     * @param string  $protocolId
     *
     * @return string
     *
     * @throws InvalidConfigurationException when the id was not set in the service
     */
    protected function getIdFromService(Service $service, $protocolId)
    {
        $id = $service->getId();
        if (null === $id) {
            $class = get_class($service);
            throw new InvalidConfigurationException(
                sprintf(
                    'Service "%s" found in protocol "%s" does not have an id. It must be set in its configuration.',
                    $class,
                    $protocolId
                )
            );
        }

        return $id;
    }
}
