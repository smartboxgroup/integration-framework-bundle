<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;

/**
 * Class InexistentProcessorMock.
 */
class InexistentProcessorMock extends Processor
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->setDescription('This processor is only a placeholder for a processor that was part of an itinerary but no longer exists in the container, with id: '.$this->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        parent::setId($id);
        $this->setDescription('This processor is only a placeholder for a processor that was part of an itinerary but no longer exists in the container, with id: '.$this->getId());
    }

    /**
     * {@inheritdoc}
     */
    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        throw new \Exception('Missing processor '.$this->getId().' not found in the container');
    }
}
