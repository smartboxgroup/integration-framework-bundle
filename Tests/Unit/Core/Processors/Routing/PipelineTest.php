<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Processors\Routing;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\Pipeline;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class PipelineTest.
 */
class PipelineTest extends \PHPUnit\Framework\TestCase
{
    /** @var Pipeline */
    private $pipeline;

    protected function setUp(): void
    {
        $this->pipeline = new Pipeline();
    }

    protected function tearDown(): void
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

        $endpointProcessor = 'endpoint_p';
        $contentRouter = 'content_router_p';

        $itineraryA = new Itinerary('Itinerary A');
        $itineraryA->addProcessorId($endpointProcessor);
        $itineraryB = new Itinerary('Itinerary B');
        $itineraryB->addProcessorId($contentRouter);

        $exchange = new Exchange(null, $itineraryA);

        $this->pipeline->setEventDispatcher($eventDispatcher);
        $this->pipeline->setItinerary($itineraryB);
        $this->pipeline->process($exchange);

        $expectedResult = [
            $contentRouter,
            $endpointProcessor,
        ];

        $this->assertEquals($expectedResult, $exchange->getItinerary()->getProcessorIds());
    }
}
