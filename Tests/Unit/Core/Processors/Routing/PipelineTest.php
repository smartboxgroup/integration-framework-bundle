<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Processors\Routing;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Processors\EndpointProcessor;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\ContentRouter;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\Pipeline;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class PipelineTest.
 */
class PipelineTest extends \PHPUnit_Framework_TestCase
{
    /** @var Pipeline */
    private $pipeline;

    protected function setUp()
    {
        $this->pipeline = new Pipeline();
    }

    protected function tearDown()
    {
        $this->pipeline = null;
    }

    public function testSetAndGetItinerary()
    {
        /** @var Itinerary|\PHPUnit_Framework_MockObject_MockObject $itinerary */
        $itinerary = $this->createMock(Itinerary::class);

        $this->pipeline->setItinerary($itinerary);
        $this->assertSame($itinerary, $this->pipeline->getItinerary());
    }

    public function testProcess()
    {
        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $endpointProcessor = new EndpointProcessor();
        $contentRouter = new ContentRouter();

        $itineraryA = new Itinerary('Itinerary A');
        $itineraryA->addProcessor($endpointProcessor);
        $itineraryB = new Itinerary('Itinerary B');
        $itineraryB->addProcessor($contentRouter);

        $exchange = new Exchange(null, $itineraryA);

        $this->pipeline->setEventDispatcher($eventDispatcher);
        $this->pipeline->setItinerary($itineraryB);
        $this->pipeline->process($exchange);

        $expectedResult = [
            $contentRouter,
            $endpointProcessor,
        ];

        $this->assertEquals($expectedResult, $exchange->getItinerary()->getProcessors());
    }
}
