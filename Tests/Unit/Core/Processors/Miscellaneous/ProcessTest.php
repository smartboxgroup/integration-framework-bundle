<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Processors\Miscellaneous;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageFactory;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageFactoryInterface;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Miscellaneous\Process;
use Smartbox\Integration\FrameworkBundle\Tests\EntityX;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Processors\FakeProcessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProcessTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $eventDispatcherMock;

    /** @var Process */
    private $processProcessor;

    /**
     * @var MessageFactoryInterface
     */
    public $factory;

    protected function setUp()
    {
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);

        $this->processProcessor = new Process();
        $this->processProcessor->setEventDispatcher($this->eventDispatcherMock);

        $this->factory = new MessageFactory();
        $this->factory->setFlowsVersion(0);
    }

    protected function tearDown()
    {
        $this->processProcessor = null;
    }

    public function testExecutionOfInternalProcessorWithSuccessResult()
    {
        $message = $this->factory->createMessage(new EntityX(5));
        $exchange = new Exchange($message);

        $internalProcessor = new FakeProcessor('id_1');
        $internalProcessor->setEventDispatcher($this->eventDispatcherMock);
        $this->processProcessor->setProcessor($internalProcessor);
        $this->processProcessor->process($exchange);

        /** @var EntityX $messageAfterProcessing */
        $messageAfterProcessing = $message->getBody();

        $this->assertEquals('5|processed by processor with id id_1', $messageAfterProcessing->getX());
    }

    public function testExecutionOfInternalProcessorWithErrorResult()
    {
        $exception = new \Exception('Fake processor exception occurred.', 111);

        $internalProcessor = new FakeProcessor('id_1', $exception);
        $internalProcessor->setEventDispatcher($this->eventDispatcherMock);

        $this->expectException(ProcessingException::class);

        $this->processProcessor->setProcessor($internalProcessor);

        $message = $this->factory->createMessage(new EntityX(5));
        $exchange = new Exchange($message);

        $this->processProcessor->process($exchange);
    }
}
