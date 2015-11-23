<?php
$loader = __DIR__.'/../../vendor/autoload.php';
if (!file_exists($loader)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$autoload = require_once $loader;

require __DIR__.'/AppKernel.php';

use Doctrine\Common\Annotations\AnnotationRegistry;

AnnotationRegistry::registerLoader('class_exists');