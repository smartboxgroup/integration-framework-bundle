<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Tools\Mapper\MapperInterface;

/**
 * Trait UsesMapper.
 */
trait UsesMapper
{
    /** @var MapperInterface */
    protected $mapper;

    /**
     * @return MapperInterface
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * @param MapperInterface $mapper
     */
    public function setMapper(MapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }
}
