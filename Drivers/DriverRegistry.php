<?php

namespace Smartbox\Integration\FrameworkBundle\Drivers;

use Smartbox\Integration\FrameworkBundle\Service;

class DriverRegistry
{

    protected $drivers;

    public function getDriver($name){
        return @$this->drivers[$name];
    }

    public function setDriver($name, Service $driver){
        $this->drivers[$name] = $driver;
    }

}