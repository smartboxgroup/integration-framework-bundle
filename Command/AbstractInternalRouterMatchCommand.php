<?php

namespace Smartbox\Integration\FrameworkBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Matcher\TraceableUrlMatcher;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractInternalRouterMatchCommand extends ContainerAwareCommand {

    public abstract function getInternalRouterName();

    public function getRouterServiceId(){
        return 'smartif.router.'.$this->getInternalRouterName();
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

    protected function execute(InputInterface $input, OutputInterface $output){
        $router = $this->getContainer()->get($this->getRouterServiceId());
        $context = $router->getContext();

        $matcher = new TraceableUrlMatcher($router->getRouteCollection(), $context);

        $pathinfo = $input->getArgument('path_info');
        if($pathinfo[0] !== '/'){
            $pathinfo = '/'.$pathinfo;
        }

        $traces = $matcher->getTraces($pathinfo);

        $matches = false;
        foreach ($traces as $trace) {
            if (TraceableUrlMatcher::ROUTE_ALMOST_MATCHES == $trace['level']) {
                $output->writeln(sprintf('<fg=yellow>Route "%s" almost matches but %s</>', $trace['name'], lcfirst($trace['log'])));
            } elseif (TraceableUrlMatcher::ROUTE_MATCHES == $trace['level']) {
                $output->writeln(sprintf('<fg=green>Route "%s" matches</>', $trace['name']));

                $routerDebugcommand = $this->getApplication()->find('router:debug:'.$this->getInternalRouterName());
                $output->writeln('');
                $routerDebugcommand->run(new ArrayInput(array('name' => $trace['name'])), $output);

                $matches = true;
            } elseif ($input->getOption('verbose')) {
                $output->writeln(sprintf('Route "%s" does not match: %s', $trace['name'], $trace['log']));
            }
        }

        if (!$matches) {
            $output->writeln(sprintf('<fg=red>None of the routes match the path "%s"</>', $input->getArgument('path_info')));

            return 1;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('router:match:'.$this->getInternalRouterName())
            ->setDefinition(array(
                new InputArgument('path_info', InputArgument::REQUIRED, 'A path info'),
            ))
            ->setDescription('Helps debug '.$this->getInternalRouterName().' by simulating a path info match')
            ->setHelp(<<<EOF
The <info>%command.name%</info> shows which routes match a given URI and which don't and for what reason:

  <info>php %command.full_name% /foo</info>

EOF
            )
        ;
    }
}