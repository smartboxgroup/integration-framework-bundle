<?php
namespace Smartbox\Integration\FrameworkBundle\Traits;

use Smartbox\Integration\FrameworkBundle\Util\MapperInterface;

trait UsesMapper
{
    /** @var  MapperInterface */
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