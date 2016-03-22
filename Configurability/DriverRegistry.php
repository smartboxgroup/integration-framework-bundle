<?php

namespace Smartbox\Integration\FrameworkBundle\Configurability;

use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class DriverRegistry.
 */
class DriverRegistry
{
    /** @var array */
    protected $drivers;

    /**
     * @param $name
     *
     * @return mixed
     */
    public function getDriver($name)
    {
        return @$this->drivers[$name];
    }

    /**
     * @param         $name
     * @param Service $driver
     */
    public function setDriver($name, Service $driver)
    {
        $this->drivers[$name] = $driver;
    }
}
