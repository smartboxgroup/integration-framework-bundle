<?php


namespace Smartbox\Integration\FrameworkBundle\Tests\Functional;


use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Processors\Processor;

abstract class ProcessorTest extends BaseTestCase
{
    /** @var  Processor */
    protected $processor;

    abstract protected function createProcessor();

    public abstract function getInvalidMessages();

    public abstract function getWorkingMessages();

    public function setUp()
    {
        parent::setUp();
        $this->processor = $this->createProcessor();
    }

    /**
     * @dataProvider getWorkingMessages
     * @param $inMessage
     * @param $outMessage
     */
    public function testWorkingMessages(MessageInterface $inMessage, MessageInterface $outMessage)
    {
        $exchange = new Exchange($inMessage);

        // Check exchange is OK
        $this->assertEquals($inMessage, $exchange->getIn());

        $this->processor->process($exchange);

        // Check result
        $this->assertEquals($outMessage, $exchange->getResult());
    }
}