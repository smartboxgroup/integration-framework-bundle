<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Producers;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Producers\DirectProducer;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerUnrecoverableException;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;

class DirectProducerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DirectProducer */
    private $directProducer;

    protected function setUp(): void
    {
        $this->directProducer = new DirectProducer();
    }

    protected function tearDown(): void
    {
        $this->directProducer = null;
    }

    public function testWhenEndpointUriIsNotFound()
    {
        $this->expectException(ProducerUnrecoverableException::class);

        /** @var EndpointInterface|\PHPUnit_Framework_MockObject_MockObject $endpoint */
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint
            ->expects($this->once())
            ->method('getURI')
            ->will($this->returnValue(false));

        $this->directProducer->send(new Exchange(), $endpoint);
    }
}
