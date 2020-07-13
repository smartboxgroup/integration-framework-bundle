<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tools\FlowExporter;


class FlowExporter
{
    /**
     * @var array
     */
    protected $producers;

    /**
     * @var array
     */
    protected $mappings;

    public function addProducers(array $producers)
    {
        $this->producers = $producers;
    }

    public function addMappings(array $mappings)
    {
        $this->mappings = $mappings;
    }
}