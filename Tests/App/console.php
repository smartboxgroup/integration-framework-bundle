<?php
// File: Tests/app/console.php

set_time_limit(0);

require_once __DIR__.'/autoload.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;

try{
    $kernel = new \Smartbox\Integration\FrameworkBundle\Tests\App\AppKernel('dev', true);
    $application = new Application($kernel);
    $application->run();
}catch (Exception $ex){
    var_dump($ex);
}