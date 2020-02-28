<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Itinerary;

use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouterResourceNotFound;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\ItineraryResolver;

class ItineraryResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var ItineraryResolver */
    private $itineraryResolver;

    protected function setUp()
    {
        $this->itineraryResolver = new ItineraryResolver();
    }

    protected function tearDown()
    {
        $this->itineraryResolver = null;
    }

    public function testGetItinerary()
    {
        $params = [InternalRouter::KEY_ITINERARY => new Itinerary()];

        /** @var InternalRouter|\PHPUnit_Framework_MockObject_MockObject $internalRouter */
        $internalRouter = $this->createMock(InternalRouter::class);
        $internalRouter
            ->expects($this->once())
            ->method('match')
            ->with($this->equalTo('v0-api://test'))
            ->will($this->returnValue($params));

        $this->itineraryResolver->setItinerariesRouter($internalRouter);

        $itinerary = $this->itineraryResolver->getItinerary('api://test', '0');

        $this->assertInstanceOf(Itinerary::class, $itinerary);
    }

    public function testGetItineraryUriWithVersion()
    {
        $uri = 'api://test';
        $version = '0';

        $itineraryUri = $this->itineraryResolver->getItineraryURIWithVersion($uri, $version);

        $this->assertSame('v0-api://test', $itineraryUri);
    }

    public function testGetItineraryParamsWhenRouteNotMatch()
    {
        $this->expectException(InternalRouterResourceNotFound::class);

        /** @var InternalRouter|\PHPUnit_Framework_MockObject_MockObject $internalRouter */
        $internalRouter = $this->createMock(InternalRouter::class);
        $internalRouter
            ->expects($this->once())
            ->method('match')
            ->with($this->equalTo('v0-api://test'))
            ->will($this->throwException(new InternalRouterResourceNotFound()));

        $this->itineraryResolver->setItinerariesRouter($internalRouter);
        $this->itineraryResolver->getItineraryParams('api://test', '0');
    }

    public function testGetItineraryParamsWhenRouteMatchIsEmpty()
    {
        $this->expectException(InternalRouterResourceNotFound::class);

        /** @var InternalRouter|\PHPUnit_Framework_MockObject_MockObject $internalRouter */
        $internalRouter = $this->createMock(InternalRouter::class);
        $internalRouter
            ->expects($this->once())
            ->method('match')
            ->with($this->equalTo('v0-api://test'))
            ->will($this->returnValue([]));

        $this->itineraryResolver->setItinerariesRouter($internalRouter);
        $this->itineraryResolver->getItineraryParams('api://test', '0');
    }

    public function testGetItineraryParamsWhenItineraryKeyDoesNotExist()
    {
        $this->expectException(InternalRouterResourceNotFound::class);

        $params = ['a' => 'b'];

        /** @var InternalRouter|\PHPUnit_Framework_MockObject_MockObject $internalRouter */
        $internalRouter = $this->createMock(InternalRouter::class);
        $internalRouter
            ->expects($this->once())
            ->method('match')
            ->with($this->equalTo('v0-api://test'))
            ->will($this->returnValue($params));

        $this->itineraryResolver->setItinerariesRouter($internalRouter);
        $this->itineraryResolver->getItineraryParams('api://test', '0');
    }

    public function testGetItineraryParamsWhenItineraryGotIsNotInstanceOfItineraryClass()
    {
        $this->expectException(\Exception::class);

        $params = [InternalRouter::KEY_ITINERARY => new \stdClass()];

        /** @var InternalRouter|\PHPUnit_Framework_MockObject_MockObject $internalRouter */
        $internalRouter = $this->createMock(InternalRouter::class);
        $internalRouter
            ->expects($this->once())
            ->method('match')
            ->with($this->equalTo('v0-api://test'))
            ->will($this->returnValue($params));

        $this->itineraryResolver->setItinerariesRouter($internalRouter);
        $this->itineraryResolver->getItineraryParams('api://test', '0');
    }

    public function testGetItineraryParams()
    {
        $params = [InternalRouter::KEY_ITINERARY => new Itinerary()];

        /** @var InternalRouter|\PHPUnit_Framework_MockObject_MockObject $internalRouter */
        $internalRouter = $this->createMock(InternalRouter::class);
        $internalRouter
            ->expects($this->once())
            ->method('match')
            ->with($this->equalTo('v0-api://test'))
            ->will($this->returnValue($params));

        $this->itineraryResolver->setItinerariesRouter($internalRouter);
        $itineraryParams = $this->itineraryResolver->getItineraryParams('api://test', '0');

        $this->assertSame($params, $itineraryParams);
    }

    public function testFilterItineraryParamsToPropagate()
    {
        $params = [
            'header_a' => null,
            'header_b' => new \stdClass(),
            'header_c' => 'string',
        ];

        $expectedParams = ['header_c' => 'string'];

        $paramsToPropagate = $this->itineraryResolver->filterItineraryParamsToPropagate($params);

        $this->assertSame($expectedParams, $paramsToPropagate);
    }
}
