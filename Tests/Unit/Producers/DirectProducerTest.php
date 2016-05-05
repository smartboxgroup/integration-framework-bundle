<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Producers;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointUnrecoverableException;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;

class DirectProducerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DirectProducer
     */
    private $directProducer;

    public function setUp()
    {
        $this->directProducer = new DirectProducer;
    }

    public function testWhenEndpointUriIsNotFound()
    {
        $endpoint = $this->getMock(EndpointInterface::class);
        $endpoint
            ->expects($this->once())
            ->method('getURI')
            ->will($this->returnValue(false));

        $this->setExpectedException(EndpointUnrecoverableException::class);

        $this->directProducer->send(new Exchange, $endpoint);
    }
}