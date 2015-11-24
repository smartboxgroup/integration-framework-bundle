<?php

namespace Smartbox\Integration\FrameworkBundle\Command;

use Smartbox\Integration\FrameworkBundle\Processors\Endpoint;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EndpointListCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
            ->setName('smartesb:endpoint:list')
            ->setDescription('List of existing async handlers')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Show list of async handlers in specific format.',
                'table'
            )
            ->addOption(
                'prefix',
                null,
                InputOption::VALUE_OPTIONAL,
                'Filter the list of endpoints showing only those endpoints which URI starts with this prefix',
                ''
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $format = $input->getOption('format');
        $container = $this->getContainer();
        $endpointsRegistry = $container->get('smartesb.registry.endpoints');

        $asyncHandlers = $endpointsRegistry->getRegisteredEndpointsIds();

        $rows = [];
        $prefix = $input->getOption('prefix');

        foreach ($asyncHandlers as $endpointId) {
            /** @var Endpoint $endpoint */
            $endpoint = $container->get($endpointId);
            $uri =  $endpoint->getURI();

            if(empty($prefix) || strpos($uri,$prefix) === 0){
                $rows[] = ['id' => $endpointId, 'uri' => $uri];
            }
        }

        switch ($format) {
            case 'table':
                $this->renderHeader();
                $this->renderTable($rows);

                break;

            case 'json':
                $this->renderJson($rows);

                break;

            default:
                throw new \InvalidArgumentException('Not supported format: ' . $format);
        }
    }

    private function renderHeader()
    {
        $this->output->writeln('<info>Endpoints:</info>');
    }

    private function renderTable(array $handlers)
    {
        $table = $this->getHelper('table');
        $table
            ->setHeaders(array('id', 'URI'))
            ->setRows($handlers)
        ;
        $table->render($this->output);
    }

    private function renderJson(array $handlers)
    {
        $this->output->writeln(json_encode($handlers));
    }
}