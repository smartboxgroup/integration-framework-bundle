<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Itinerary;

use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouterResourceNotFound;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\ItineraryResolver;

class ItineraryResolverTest extends \PHPUnit_Framework_TestCase
{
    private $itineraryResolver;

    public function setUp()
    {
        $this->itineraryResolver = new ItineraryResolver;
    }

    public function tearDown()
    {
        $this->itineraryResolver = null;
    }

    public function testGetItinerary()
    {
        $params = [InternalRouter::KEY_ITINERARY => new Itinerary];

        $internalRouter = $this->createMock(InternalRouter::class);
        $internalRouter
            ->expects($this->once())
            ->method('match')
            ->will($this->returnValue($params));

        $this->itineraryResolver->setItinerariesRouter($internalRouter);

        $itinerary = $this->itineraryResolver->getItinerary('api://test', '0');

        $this->assertInstanceOf(Itinerary::class, $itinerary);
    }

    public function testGetItineraryUriWithVersion()
    {
        $uri     = 'api://test';
        $version = '0';

        $itineraryUri = $this->itineraryResolver->getItineraryURIWithVersion($uri, $version);

        $this->assertSame('v0-api://test', $itineraryUri);
    }

    public function testGetItineraryParamsWhenRouteNotMatch()
    {
        $internalRouter = $this->createMock(InternalRouter::class);
        $internalRouter
            ->expects($this->once())
            ->method('match')
            ->will($this->throwException(new InternalRouterResourceNotFound));

        $this->expectException(InternalRouterResourceNotFound::class);

        $this->itineraryResolver->setItinerariesRouter($internalRouter);
        $this->itineraryResolver->getItineraryParams('api://test', '0');
    }

    public function testGetItineraryParamsWhenRouteMatchIsEmpty()
    {
        $internalRouter = $this->createMock(InternalRouter::class);
        $internalRouter
            ->expects($this->once())
            ->method('match')
            ->will($this->returnValue([]));

        $this->expectException(InternalRouterResourceNotFound::class);

        $this->itineraryResolver->setItinerariesRouter($internalRouter);
        $this->itineraryResolver->getItineraryParams('api://test', '0');
    }

    public function testGetItineraryParamsWhenItineraryKeyDoesNotExist()
    {
        $params = ['a' => 'b'];

        $internalRouter = $this->createMock(InternalRouter::class);
        $internalRouter
            ->expects($this->once())
            ->method('match')
            ->will($this->returnValue($params));

        $this->expectException(InternalRouterResourceNotFound::class);

        $this->itineraryResolver->setItinerariesRouter($internalRouter);
        $this->itineraryResolver->getItineraryParams('api://test', '0');
    }

    public function testGetItineraryParamsWhenItineraryGotIsNotInstanceOfItineraryClass()
    {
        $params = [InternalRouter::KEY_ITINERARY => new \stdClass];

        $internalRouter = $this->createMock(InternalRouter::class);
        $internalRouter
            ->expects($this->once())
            ->method('match')
            ->will($this->returnValue($params));

        $this->expectException(\Exception::class);

        $this->itineraryResolver->setItinerariesRouter($internalRouter);
        $this->itineraryResolver->getItineraryParams('api://test', '0');
    }

    public function testGetItineraryParams()
    {
        $params = [InternalRouter::KEY_ITINERARY => new Itinerary];

        $internalRouter = $this->createMock(InternalRouter::class);
        $internalRouter
            ->expects($this->once())
            ->method('match')
            ->will($this->returnValue($params));


        $this->itineraryResolver->setItinerariesRouter($internalRouter);
        $itineraryParams = $this->itineraryResolver->getItineraryParams('api://test', '0');

        $this->assertSame($params, $itineraryParams);
    }

    public function testFilterItineraryParamsToPropagate()
    {
        $params = [
            'header_a' => null,
            'header_b' => new \stdClass,
            'header_c' => 'string',
        ];

        $expectedParams = ['header_c' => 'string'];

        $paramsToPropagate = $this->itineraryResolver->filterItineraryParamsToPropagate($params);

        $this->assertSame($expectedParams, $paramsToPropagate);
    }
}