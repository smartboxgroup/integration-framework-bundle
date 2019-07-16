<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Processors\Routing;

use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Itinerary\ItineraryResolver;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\RecipientList;
use Smartbox\Integration\FrameworkBundle\Events\NewExchangeEvent;
use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class RecipientListTest.
 */
class RecipientListTest extends TestCase
{
    /** @var RecipientList */
    private $recipientList;

    protected function setUp()
    {
        $this->recipientList = new RecipientList();
    }

    protected function tearDown()
    {
        $this->recipientList = null;
    }

    public function testItShouldSetAndGetDelimiter()
    {
        $delimiter = '#';

        $this->recipientList->setDelimiter($delimiter);
        $this->assertSame($delimiter, $this->recipientList->getDelimiter());
    }

    public function testItShouldSetAndGetExpression()
    {
        $expression = "exchange.getHeader('recipientList')";

        $this->recipientList->setExpression($expression);
        $this->assertSame($expression, $this->recipientList->getExpression());
    }

    public function testItShouldSetAndGetAnAggregationStrategy()
    {
        $strategy = RecipientList::AGGREGATION_STRATEGY_FIRE_AND_FORGET;

        $this->recipientList->setAggregationStrategy($strategy);
        $this->assertSame($strategy, $this->recipientList->getAggregationStrategy());
    }

    public function testItShouldNotSetAnUnsupportedAggregationStrategy()
    {
        $this->expectException(\InvalidArgumentException::class);

        $strategy = 'unsupported aggregation strategy';
        $this->recipientList->setAggregationStrategy($strategy);
    }

    public function testItShouldNotEvaluateExpressionInProcess()
    {
        $this->expectException(ProcessingException::class);

        $expression = 'not good expression';
        $exchange  = $this->createMock(Exchange::class);

        $evaluator = $this->createMock(ExpressionEvaluator::class);
        $evaluator
            ->expects($this->once())
            ->method('evaluateWithExchange')
            ->with($this->equalTo($expression), $this->equalTo($exchange))
            ->will($this->throwException(new \Exception));

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->recipientList->setExpression($expression);
        $this->recipientList->setEvaluator($evaluator);
        $this->recipientList->setEventDispatcher($eventDispatcher);
        $this->recipientList->process($exchange);
    }

    public function testItShouldDispatchAnEventForEveryRecipientOnProcess()
    {
        $context = $this->createMock(Context::class);
        $context
            ->expects($this->any())
            ->method('get')
            ->with($this->equalTo('version'))
            ->will($this->returnValue(1));

        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($context));

        $exchange = $this->createMock(Exchange::class);
        $exchange
            ->expects($this->any())
            ->method('getIn')
            ->will($this->returnValue($message));
        $exchange
            ->expects($this->exactly(2))
            ->method('getItinerary')
            ->will($this->returnValue(new Itinerary()));
        $exchange
            ->expects($this->any())
            ->method('getHeaders')
            ->will($this->returnValue(['a' => 'b']));
        $exchange
            ->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue('123'));

        $expression = "exchange.getHeader('recipientList')";
        $recipientList = 'route_a,route_b';

        $evaluator = $this->createMock(ExpressionEvaluator::class);
        $evaluator
            ->expects($this->once())
            ->method('evaluateWithExchange')
            ->with($this->equalTo($expression), $this->equalTo($exchange))
            ->will($this->returnValue($recipientList));

        $itineraryParams = ['_itinerary' => $this->createMock(Itinerary::class)];

        $itineraryResolver = $this->createMock(ItineraryResolver::class);
        $itineraryResolver
            ->expects($this->exactly(2))
            ->method('getItineraryParams')
            ->will($this->returnValue($itineraryParams));
        $itineraryResolver
            ->expects($this->exactly(2))
            ->method('filterItineraryParamsToPropagate')
            ->with($this->equalTo($itineraryParams))
            ->will($this->returnValue(['b' => 'c']));

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->recipientList->setDelimiter(',');
        $this->recipientList->setExpression($expression);
        $this->recipientList->setAggregationStrategy(RecipientList::AGGREGATION_STRATEGY_FIRE_AND_FORGET);
        $this->recipientList->setEvaluator($evaluator);
        $this->recipientList->setItineraryResolver($itineraryResolver);
        $this->recipientList->setEventDispatcher($eventDispatcher);

        $dispatchedEventsCounter = 0;

        $eventDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->with($this->callback(function ($eventName) use (&$dispatchedEventsCounter) {
                if (NewExchangeEvent::TYPE_NEW_EXCHANGE_EVENT === $eventName) {
                    ++$dispatchedEventsCounter;
                }

                return true;
            }), $this->anything())
        ;

        $this->recipientList->process($exchange);

        $this->assertSame(2, $dispatchedEventsCounter);
    }
}
