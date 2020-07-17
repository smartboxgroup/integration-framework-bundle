<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tools\FlowExporter;

use Smartbox\Integration\FrameworkBundle\Tools\Mapper\MapperInterface;

class Unmapper implements MapperInterface
{
    /**
     * @var array
     */
    protected $mappings;

    public function map($obj, $mappingName, $context = [])
    {
        if (isset($this->mappings[$mappingName])) {
            return $this->mappings[$mappingName];
        }

        throw new \LogicException(sprintf('[Unmapper] Mapping "%" not found', $mappingName));
    }

    public function mapAll($elements, $mappingName, $context = [])
    {
        if (isset($this->mappings[$mappingName])) {
            return ['array' => $this->mappings[$mappingName]];
        }

        throw new \LogicException(sprintf('[Unmapper] Mapping "%" not found', $mappingName));
    }

    public function addMappings(array $mappings)
    {
        $this->mappings = $mappings;
    }
}
