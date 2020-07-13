<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Command;

use Smartbox\Integration\FrameworkBundle\Tools\FlowExporter\FlowExporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportFlowsCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'smartesb:flows:export';

    /**
     * @var FlowExporter
     */
    private $exporter;

    public function __construct(FlowExporter $exporter)
    {
        $this->exporter = $exporter;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Exports the current flows and mappings.');
        $this->setHelp('Currently no file is dumped, results are just shown on screen. Only outbound flows are processed.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }
}
