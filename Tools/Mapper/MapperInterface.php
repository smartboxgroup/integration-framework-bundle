<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Mapper;

/**
 * Interface MapperInterface.
 */
interface MapperInterface
{
    /**
     * @param mixed $obj
     * @param string $mappingName
     * @param array $context
     *
     * @return mixed
     */
    public function map($obj, $mappingName, $context);

    /**
     * @param array $elements
     * @param string $mappingName
     * @param array $context
     *
     * @return mixed
     */
    public function mapAll($elements, $mappingName, $context);

    /**
     * @param array $mappings
     *
     * @return mixed
     */
    public function addMappings(array $mappings);
}
