<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Processors;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Tests\EntityX;
use JMS\Serializer\Annotation as JMS;

/**
 * Class FakeProcessor
 * @package Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Processors
 */
class FakeProcessor extends Processor
{
    /**
     * @var \Exception
     */
    protected $exception;

    public function __construct($id, \Exception $exception = null)
    {
        $this->setId($id);
        $this->exception = $exception;
    }
    
    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        if ($this->exception) {
            throw $this->exception;
        }

        /** @var EntityX $body */
        $body = $exchange->getIn()->getBody();
        $body->setX($body->getX() . $this->getProcessedMessage());
    }

    protected function getProcessedMessage()
    {
        return '|processed by processor with id ' . $this->getId();
    }
}
