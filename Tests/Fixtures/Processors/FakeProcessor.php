<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Processors;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Tests\EntityX;

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
