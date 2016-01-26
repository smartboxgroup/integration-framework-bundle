<?php

namespace Smartbox\Integration\FrameworkBundle\Traits;

use Smartbox\Integration\FrameworkBundle\Drivers\DriverRegistry;

/**
 * Class UsesDriverRegistry
 * @package Smartbox\Integration\FrameworkBundle\Traits
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
