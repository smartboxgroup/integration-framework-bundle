<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Configurability\DriverRegistry;

/**
 * Trait UsesDriverRegistry.
 */
trait UsesDriverRegistry
{
    /** @var DriverRegistry */
    protected $driverRegistry;

    /**
     * @return DriverRegistry
     */
    public function getDriverRegistry()
    {
        return $this->driverRegistry;
    }

    /**
     * @param DriverRegistry $driverRegistry
     */
    public function setDriverRegistry(DriverRegistry $driverRegistry)
    {
        $this->driverRegistry = $driverRegistry;
    }
}
