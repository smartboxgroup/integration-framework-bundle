<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit;

use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class ServiceTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit
 */
class ServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testSetIdAndGetId()
    {
        $id = 12345;

        /** @var Service|\PHPUnit_Framework_MockObject_MockObject $serviceMock */
        $serviceMock = $this->getMockBuilder(Service::class)
            ->setMethods(null)
            ->getMock()
        ;

        $serviceMock->setId($id);
        $this->assertEquals($id, $serviceMock->getId());
    }
}
