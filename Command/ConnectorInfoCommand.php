<?php

namespace Smartbox\Integration\FrameworkBundle\Command;


use Smartbox\Integration\FrameworkBundle\Connectors\ConnectorInterface;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\TraceableUrlMatcher;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class ConnectorInfoCommand extends ContainerAwareCommand {

    protected function execute(InputInterface $input, OutputInterface $output){
        $connectorId = $input->getArgument('connector_id');
        $routerConnectors = $this->getContainer()->get('smartesb.router.connectors');

        if($connectorId){
            $connectors = array($connectorId);
        }else{
            $connectors = array_reduce($routerConnectors->getRouteCollection()->all(), function(array $res, Route $route){
                $connectorId = $route->getDefault(InternalRouter::KEY_CONNECTOR);
                if(!in_array($connectorId,$res)){
                    $res[] = substr($connectorId,1);
                }
                return $res;
            }, array());
        }
        $connectors = array_unique($connectors);

        foreach($connectors as $id){
            $this->explainConnector($id,$input,$output);
        }

    }

    protected function explainConnector($connectorId, $input, $output){
        $routerConnectors = $this->getContainer()->get('smartesb.router.connectors');

        /** @var ConnectorInterface $connector */
        $connector = $this->getContainer()->get($connectorId);
        $class = get_class($connector);
        $defaults = $connector->getDefaultOptions();

        $optionsExplained = '';

        foreach ($connector->getAvailableOptions() as $key => list($description, $possibleOptions)) {
            $optionsExplained.= "\n";
            $optionsExplained .= "\t- <comment>$key: </comment><info>$description</info>\n";

            if(array_key_exists($key,$defaults)){
                $default = var_export($defaults[$key],true);
                $optionsExplained .= "\t\t- <comment>Default: </comment><info>$default</info>\n";
            }

            if(!empty($possibleOptions)){
                $optionsExplained .= "\t\t- <comment>Possible values: </comment>\n";
                foreach($possibleOptions as $key => $value){
                    $value = var_export($value,true);
                    $optionsExplained .= "\t\t\t- <comment>$key: </comment><info>$value</info>\n";
                }
            }
        }


        $routes = array_filter($routerConnectors->getRouteCollection()->all(), function(Route $route) use($connectorId){
            return $route->getDefault(InternalRouter::KEY_CONNECTOR) == '@'.$connectorId;
        });

        $routesExplained  = '';

        /**
         * @var  string $routeName
         * @var  Route $route
         */
        foreach($routes as $routeName => $route){
            $routesExplained .= "\n";
            $pattern = substr($route->getPattern(),1);
            $routesExplained .= "\t- <comment>$routeName: </comment><info>$pattern</info>\n";

            $defaults = $route->getDefaults();
            unset($defaults[InternalRouter::KEY_CONNECTOR]);

            if(!empty($defaults)){
                foreach($defaults as $key => $value){
                    $value = var_export($value,true);
                    $routesExplained .= "\t\t<comment>- $key: </comment><info>$value</info>\n";
                }
            }
        }

        $output->write(
            <<<EOF

    <comment>Id: </comment> <info>$connectorId</info>

    <comment>Class: </comment><info>$class</info>

    <comment>Options: </comment>
    $optionsExplained

    <comment>Routes: </comment>
    $routesExplained

----------------------------------------------------------------------------------------------------------------
EOF
        );
        $output->writeln("");;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('smartesb:connectors:info')
            ->setDefinition(array(
                new InputArgument('connector_id', InputArgument::OPTIONAL, 'A connector id'),
            ))
            ->setDescription('Validates connector routes and endpoint URIs')
        ;
    }
}