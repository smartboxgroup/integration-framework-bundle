<?php

namespace Smartbox\Integration\FrameworkBundle\Tests;

use Smartbox\Integration\FrameworkBundle\Tests\App\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class BaseKernelTestCase
 * @package Smartbox\Integration\FrameworkBundle\Tests
 */
class BaseKernelTestCase extends KernelTestCase {

    public static function getKernelClass(){
        return AppKernel::class;
    }

    public function setUp(){
        $this->bootKernel();
    }

    public function tearDown(){
        parent::tearDown();
    }

    public function getContainer(){
        return self::$kernel->getContainer();
    }
}
