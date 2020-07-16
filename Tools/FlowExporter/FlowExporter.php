<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tools\FlowExporter;


use Smartbox\BifrostBundle\Producers\VmsProducer;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FlowExporter implements ContainerAwareInterface
{
    /**
     * @var array
     */
    protected $producers;

    /**
     * @var array
     */
    protected $mappings;
    /**
     * @var ContainerInterface|null
     *
     * @TODO replace with service locator
     */
    protected $container;

    public function addProducers(array $producers)
    {
        $this->producers = $producers;
    }

    public function addMappings(array $mappings)
    {
        $this->mappings = $mappings;
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function export()
    {
        $this->buildProducers($this->producers);
    }

    protected function buildProducers(array $producers)
    {
        foreach ($producers as $producerName) {
            /** @var VmsProducer $producer */
            $producer = $this->container->get($producerName);
            $test=$producer->getOptionsDescriptions();
            $test=1;
        }
    }
}
