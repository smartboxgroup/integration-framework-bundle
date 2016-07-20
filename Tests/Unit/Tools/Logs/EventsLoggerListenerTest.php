<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Tools\Logs;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Transformation\Transformer;
use Smartbox\Integration\FrameworkBundle\Events\Event;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Events\FakeErrorEvent;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Events\FakeHandlerEvent;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Events\FakeProcessEvent;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Processors\FakeProcessor;
use Smartbox\Integration\FrameworkBundle\Tools\Logs\EventsLoggerListener;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EventsLoggerListenerTest.
 */
class EventsLoggerListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventsLoggerListener */
    private $listener;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;


    protected function setUp()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->createMock(RequestStack::class);

        $this->logger   = $this->createMock(LoggerInterface::class);
        $this->listener = new EventsLoggerListener($this->logger, $requestStack);
    }

    protected function tearDown()
    {
        $this->logger   = null;
        $this->listener = null;
    }

    public function EventContextProvider()
    {
        return [
            'Case when event occurs' => $this->getContextForEvent(),
            'Case when handler event occurs' => $this->getContextForHandlerEvent(),
            'Case when process event occurs' => $this->getContextForProcessEvent(),
            'Case when process event with endpoint uri occurs' => $this->getContextForProcessEventWithEndpointUri(),
            'Case when process event with processor implementing log exchange details interface occurs' =>
                $this->getContextForProcessEventWithProcessorImplementingLogExchangeDetailsInterface(),
            'Case when processing error event occurs'=> $this->getContextForProcessingErrorEvent(),
        ];
    }

    /**
     * @dataProvider EventContextProvider
     *
     * @param Event $event
     * @param array $expectedContext
     * @param string $logLevel
     */
    public function testLogEvent(Event $event, array $expectedContext, $logLevel)
    {
        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($logLevel),
                $this->isType('string'),
                $this->equalTo($expectedContext)
            );

        $this->listener->onEvent($event);
    }

    private function getContextForEvent()
    {
        return [
            'event'            => $this->getMockForAbstractClass(Event::class),
            'expected_context' => [
                'event_name'    => null,
                'event_details' => '',
            ],
            "logLevel" => LogLevel::DEBUG
        ];
    }

    private function getContextForHandlerEvent()
    {
        $exchange = new Exchange;
        $exchange->setHeaders([
            'from'  => 'test://endpoint',
            'async' => false,
        ]);

        $event = new FakeHandlerEvent;
        $event->setEventName(FakeHandlerEvent::BEFORE_HANDLE_EVENT_NAME);
        $event->setExchange($exchange);

        return [
            'event'            => $event,
            'expected_context' => [
                'event_name'    => FakeHandlerEvent::BEFORE_HANDLE_EVENT_NAME,
                'event_details' => null,
                'exchange'      => [
                    'id'     => $exchange->getId(),
                    'uri'    => 'test://endpoint',
                    'type'   => 'sync',
                    'detail' => $exchange
                ]
            ],
            "logLevel" => LogLevel::DEBUG
        ];
    }

    private function getContextForProcessEvent()
    {
        $exchange = new Exchange;
        $exchange->setHeaders([
            'from'  => 'test://endpoint',
            'async' => true,
        ]);

        $processingContext = new SerializableArray;

        $processor = new FakeProcessor('processor_1');
        $processor->setDescription('Processor 1 description');

        $event = new FakeProcessEvent;
        $event->setEventName(FakeProcessEvent::TYPE_BEFORE);
        $event->setExchange($exchange);
        $event->setProcessingContext($processingContext);
        $event->setProcessor($processor);

        return [
            'event'            => $event,
            'expected_context' => [
                'event_name'    => FakeProcessEvent::TYPE_BEFORE,
                'event_details' => '',
                'exchange'      => [
                    'id'     => $exchange->getId(),
                    'uri'    => 'test://endpoint',
                    'type'   => 'async',
                ],
                'processor'     => [
                    'id'          => 'processor_1',
                    'name'        => FakeProcessor::class,
                    'description' => 'Processor 1 description',
                ]
            ],
            "logLevel" => LogLevel::DEBUG
        ];
    }

    private function getContextForProcessEventWithEndpointUri()
    {
        $exchange = new Exchange;
        $exchange->setHeaders([
            'from'  => 'test://endpoint',
            'async' => true,
        ]);

        $processingContext = new SerializableArray(['resolved_uri' => 'test://endpoint_uri']);

        $processor = new FakeProcessor('processor_1');
        $processor->setDescription('Processor 1 description');

        $event = new FakeProcessEvent;
        $event->setEventName(FakeProcessEvent::TYPE_BEFORE);
        $event->setExchange($exchange);
        $event->setProcessingContext($processingContext);
        $event->setProcessor($processor);

        return [
            'event'            => $event,
            'expected_context' => [
                'event_name'    => FakeProcessEvent::TYPE_BEFORE,
                'event_details' => '',
                'endpoint_uri'  => 'test://endpoint_uri',
                'exchange'      => [
                    'id'     => $exchange->getId(),
                    'uri'    => 'test://endpoint',
                    'type'   => 'async',
                ],
                'processor'     => [
                    'id'          => 'processor_1',
                    'name'        => FakeProcessor::class,
                    'description' => 'Processor 1 description',
                ]
            ],
            "logLevel" => LogLevel::DEBUG
        ];
    }

    private function getContextForProcessEventWithProcessorImplementingLogExchangeDetailsInterface()
    {
        $exchange = new Exchange;
        $exchange->setHeaders([
            'from'  => 'test://endpoint',
            'async' => true
        ]);

        $processingContext = new SerializableArray;

        $processor = new Transformer;
        $processor->setId('processor_1');
        $processor->setDescription('Processor 1 description');

        $event = new FakeProcessEvent;
        $event->setEventName(FakeProcessEvent::TYPE_BEFORE);
        $event->setExchange($exchange);
        $event->setProcessingContext($processingContext);
        $event->setProcessor($processor);

        return [
            'event'            => $event,
            'expected_context' => [
                'event_name'    => FakeProcessEvent::TYPE_BEFORE,
                'event_details' => '',
                'exchange'      => [
                    'id'     => $exchange->getId(),
                    'uri'    => 'test://endpoint',
                    'type'   => 'async',
                    'detail' => $exchange,
                ],
                'processor'     => [
                    'id'          => 'processor_1',
                    'name'        => Transformer::class,
                    'description' => 'Processor 1 description',
                ]
            ],
            "logLevel" => LogLevel::DEBUG
        ];
    }

    private function getContextForProcessingErrorEvent()
    {
        $processor = new FakeProcessor('processor_1');
        $processor->setDescription('Processor 1 description');

        $exchange = new Exchange;
        $exchange->setHeaders([
            'from'  => 'test://endpoint',
            'async' => true,
        ]);

        $exception = new \Exception('exception message');

        $processingContext = new SerializableArray;

        $event = new FakeErrorEvent($processor, $exchange, $exception);
        $event->setEventName(FakeErrorEvent::TYPE_BEFORE);
        $event->setProcessingContext($processingContext);

        return [
            'event'            => $event,
            'expected_context' => [
                'event_name'    => FakeErrorEvent::TYPE_BEFORE,
                'event_details' => '',
                'exchange'      => [
                    'id'     => $exchange->getId(),
                    'uri'    => 'test://endpoint',
                    'type'   => 'async',
                ],
                'processor'     => [
                    'id'          => 'processor_1',
                    'name'        => FakeProcessor::class,
                    'description' => 'Processor 1 description',
                ],
                'exception'     => $exception,
            ],
            "logLevel" => LogLevel::ERROR
        ];
    }
}
