<?php

namespace Smartbox\Integration\FrameworkBundle\Command;


use Smartbox\Integration\FrameworkBundle\Producers\ProducerInterface;
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

class producerInfoCommand extends ContainerAwareCommand {

    protected function execute(InputInterface $input, OutputInterface $output){
        $producerId = $input->getArgument('producer_id');
        $routerProducers = $this->getContainer()->get('smartesb.router.endpoints');

        if($producerId){
            $producers = array($producerId);
        }else{
            $producers = array_reduce($routerProducers->getRouteCollection()->all(), function(array $res, Route $route){
                $producerId = $route->getDefault(InternalRouter::KEY_producer);
                if(!in_array($producerId,$res)){
                    $res[] = substr($producerId,1);
                }
                return $res;
            }, array());
        }
        $producers = array_unique($producers);

        foreach($producers as $id){
            $this->explainProducer($id,$input,$output);
        }

    }

    protected function explainProducer($producerId, $input, $output){
        $routerProducers = $this->getContainer()->get('smartesb.router.endpoints');

        /** @var ProducerInterface $producer */
        $producer = $this->getContainer()->get($producerId);
        $class = get_class($producer);
        $defaults = $producer->getDefaultOptions();

        $optionsExplained = '';

        foreach ($producer->getAvailableOptions() as $key => list($description, $possibleOptions)) {
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


        $routes = array_filter($routerProducers->getRouteCollection()->all(), function(Route $route) use($producerId){
            return $route->getDefault(InternalRouter::KEY_producer) == '@'.$producerId;
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
            unset($defaults[InternalRouter::KEY_producer]);

            if(!empty($defaults)){
                foreach($defaults as $key => $value){
                    $value = var_export($value,true);
                    $routesExplained .= "\t\t<comment>- $key: </comment><info>$value</info>\n";
                }
            }
        }

        $output->write(
            <<<EOF

    <comment>Id: </comment> <info>$producerId</info>

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
            ->setName('smartesb:producers:info')
            ->setDefinition(array(
                new InputArgument('producer_id', InputArgument::OPTIONAL, 'A producer id'),
            ))
            ->setDescription('Validates producer routes and endpoint URIs')
        ;
    }
}