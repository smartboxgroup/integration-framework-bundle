<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Mapper;


interface MapperInterface {

    /**
     * @param $obj mixed
     * @param $mappingName string
     * @return mixed
     */
    public function map($obj, $mappingName);

    /**
     * @param $elements array
     * @param $mappingName string
     * @return mixed
     */
    public function mapAll(array $elements, $mappingName);

    /**
     * @param array $mappings
     * @return mixed
     */
    public function addMappings(array $mappings);

}