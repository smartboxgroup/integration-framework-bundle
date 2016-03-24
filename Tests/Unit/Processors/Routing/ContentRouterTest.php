<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Processors\Routing;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\ContentRouter;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\WhenClause;
use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class ContentRouterTest.
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\ContentRouter
 */
class ContentRouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentRouter
     */
    private $contentRouter;

    public function setUp()
    {
        $this->contentRouter = new ContentRouter();
    }

    /**
     * @return array
     */
    public function dataProviderForWhenClauses()
    {
        return [
            [[new WhenClause()]],
            [[new WhenClause('condition')]],
            [[new WhenClause('condition', new Itinerary())]],
            [[new WhenClause(), new WhenClause('condition', new Itinerary())]],
            [[new WhenClause(), new WhenClause(null, new Itinerary())]],
        ];
    }

    /**
     * @covers ::addWhen
     * @dataProvider dataProviderForWhenClauses
     *
     * @param WhenClause[] $whenClauses
     */
    public function testAddWhen(array $whenClauses)
    {
        foreach ($whenClauses as $whenClause) {
            $this->contentRouter->addWhen($whenClause->getCondition(), $whenClause->getItinerary());
        }

        $this->assertAttributeEquals($whenClauses, 'clauses', $this->contentRouter);
    }

    /**
     * @covers ::doProcess
     * @covers ::setOtherwise
     */
    public function testDoProcessForWhenConditions()
    {
        $itinerary = new Itinerary();

        /** @var Itinerary|\PHPUnit_Framework_MockObject_MockObject $itineraryMock */
        $itineraryMock = $this->getMock(Itinerary::class);
        $itineraryMock
            ->expects($this->once())
            ->method('prepend')
            ->with($itinerary)
        ;

        /** @var Exchange|\PHPUnit_Framework_MockObject_MockObject $exchangeMock */
        $exchangeMock = $this->getMock(Exchange::class, ['getItinerary']);
        $exchangeMock
            ->expects($this->any())
            ->method('getItinerary')
            ->will($this->returnValue($itineraryMock))
        ;
        $exchangeMock
            ->expects($this->any())
            ->method('getIn')
            ->will($this->returnValue('message'))
        ;

        /** @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject $eventDispatcherMock */
        $eventDispatcherMock = $this->getMock(EventDispatcher::class);

        /** @var ExpressionEvaluator|\PHPUnit_Framework_MockObject_MockObject $evaluatorMock */
        $evaluatorMock = $this->getMockBuilder(ExpressionEvaluator::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $evaluatorMock
            ->expects($this->any())
            ->method('evaluateWithExchange')
            ->will(
                $this->returnCallback(function ($expression, $ex) {
                    switch ($expression) {
                        case 'condition_which_fails_1':
                        case 'condition_which_fails_2':
                            return false;
                            break;
                        case 'condition_which_passes_1':
                        case 'condition_which_passes_2':
                            return true;
                            break;

                    }
                })
            )
        ;

        $this->contentRouter->setEvaluator($evaluatorMock);

        $this->contentRouter->addWhen('condition_which_fails_1', new Itinerary());
        $this->contentRouter->addWhen('condition_which_fails_2', new Itinerary());
        $this->contentRouter->addWhen('condition_which_passes_1', $itinerary);
        $this->contentRouter->addWhen('condition_which_passes_2', new Itinerary());

        $this->contentRouter->setEventDispatcher($eventDispatcherMock);
        $this->contentRouter->process($exchangeMock);
    }

    /**
     * @covers ::doProcess
     * @covers ::setOtherwise
     */
    public function testDoProcessForFallbackItinerary()
    {
        $itinerary = new Itinerary();

        /** @var Itinerary|\PHPUnit_Framework_MockObject_MockObject $itineraryMock */
        $itineraryMock = $this->getMock(Itinerary::class);
        $itineraryMock
            ->expects($this->once())
            ->method('prepend')
            ->with($itinerary)
        ;

        /** @var Exchange|\PHPUnit_Framework_MockObject_MockObject $exchangeMock */
        $exchangeMock = $this->getMock(Exchange::class, ['getItinerary']);
        $exchangeMock
            ->expects($this->any())
            ->method('getItinerary')
            ->will($this->returnValue($itineraryMock))
        ;

        /** @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject $eventDispatcherMock */
        $eventDispatcherMock = $this->getMock(EventDispatcher::class);

        $this->contentRouter->setOtherwise($itinerary);
        $this->contentRouter->setEventDispatcher($eventDispatcherMock);
        $res = $this->contentRouter->process($exchangeMock);
        $this->assertTrue($res);
    }
}
