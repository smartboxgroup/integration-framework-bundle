<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Mapper;

/**
 * Interface MapperInterface.
 */
interface MapperInterface
{
    /**
     * @param $obj mixed
     * @param $mappingName string
     *
     * @return mixed
     */
    public function map($obj, $mappingName);

    /**
     * @param mixed array
     * @param $mappingName string
     *
     * @return mixed
     */
    public function mapAll($elements, $mappingName);

    /**
     * @param array $mappings
     *
     * @return mixed
     */
    public function addMappings(array $mappings);
}
