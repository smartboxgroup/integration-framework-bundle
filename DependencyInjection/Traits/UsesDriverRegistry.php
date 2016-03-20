<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Configurability\DriverRegistry;

/**
 * Class UsesDriverRegistry
 * @package Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits
 */
trait UsesDriverRegistry
{
    /** @var \Smartbox\Integration\FrameworkBundle\Core\\Smartbox\Integration\FrameworkBundle\Configurability\DriverRegistry */
    protected $driverRegistry;

    /**
     * @return DriverRegistry
     */
    public function getDriverRegistry()
    {
        return $this->driverRegistry;
    }

    /**
     * @param \Smartbox\Integration\FrameworkBundle\Configurability\DriverRegistry $driverRegistry
     */
    public function setDriverRegistry(DriverRegistry $driverRegistry)
    {
        $this->driverRegistry = $driverRegistry;
    }
}
