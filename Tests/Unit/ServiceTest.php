<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit;

use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class ServiceTest.
 */
class ServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testSetIdAndGetId()
    {
        $id = 12345;

        /** @var Service|\PHPUnit_Framework_MockObject_MockObject $serviceMock */
        $serviceMock = $this->getMockForAbstractClass(Service::class);

        $serviceMock->setId($id);
        $this->assertEquals($id, $serviceMock->getId());
    }
}
