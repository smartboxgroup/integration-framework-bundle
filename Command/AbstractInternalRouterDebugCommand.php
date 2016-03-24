<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Route;
use Symfony\Bundle\FrameworkBundle\Console\Helper\DescriptorHelper;

/**
 * Class AbstractInternalRouterDebugCommand
 */
abstract class AbstractInternalRouterDebugCommand extends ContainerAwareCommand
{
    abstract public function getInternalRouterName();

    public function getRouterServiceId()
    {
        return 'smartesb.router.'.$this->getInternalRouterName();
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        if (!$this->getContainer()->has($this->getRouterServiceId())) {
            return false;
        }
        $router = $this->getContainer()->get($this->getRouterServiceId());
        if (!$router instanceof RouterInterface) {
            return false;
        }

        return parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('smartesb:debug:router:'.$this->getInternalRouterName())
            ->setAliases(array(
                'router:debug:'.$this->getInternalRouterName(),
            ))
            ->setDefinition(array(
                new InputArgument('name', InputArgument::OPTIONAL, 'A '.$this->getInternalRouterName().' name'),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, xml, json, or md)', 'txt'),
                new InputOption('raw', null, InputOption::VALUE_NONE, 'To output raw route(s)'),
            ))
            ->setDescription('Displays current routes for an application')
            ->setHelp(<<<EOF
The <info>%command.name%</info> displays the configured routes:

  <info>php %command.full_name%</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException When route does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $router = $this->getContainer()->get($this->getRouterServiceId());
        $name = $input->getArgument('name');
        $helper = new DescriptorHelper();

        if ($name) {
            $route = $router->getRouteCollection()->get($name);
            if (!$route) {
                throw new \InvalidArgumentException(sprintf('The route "%s" does not exist.', $name));
            }
            $this->convertItinerary($route);
            $helper->describe($output, $route, array(
                'format' => $input->getOption('format'),
                'raw_text' => $input->getOption('raw'),
                'name' => $name,
            ));
        } else {
            $routes = $router->getRouteCollection();

            foreach ($routes as $route) {
                $this->convertItinerary($route);
            }

            $helper->describe($output, $routes, array(
                'format' => $input->getOption('format'),
                'raw_text' => $input->getOption('raw'),
                false,
            ));
        }
    }

    private function convertItinerary(Route $route)
    {
        $serializer = $this->getContainer()->get('serializer');

        if ($route->hasDefault(InternalRouter::KEY_ITINERARY)) {
            $serviceName = str_replace('@', '', $route->getDefault(InternalRouter::KEY_ITINERARY));
            $itineraryService = $this->getContainer()->get($serviceName);
            $itinerary = $serializer->serialize($itineraryService, 'array');
            $route->setDefault(InternalRouter::KEY_ITINERARY, $itinerary['processors']);
        }
    }
}
