<?php

namespace Smartbox\Integration\FrameworkBundle\Serialization;


use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Processors\Processor;
class InexistentProcessorMock extends Processor {

    public function __construct(){
        parent::__construct();
        $this->setDescription("This processor is only a placeholder for a processor that was part of an itinerary but no longer exists in the container, with id: ".$this->getId());
    }

    public function setId($id){
        parent::setId($id);
        $this->setDescription("This processor is only a placeholder for a processor that was part of an itinerary but no longer exists in the container, with id: ".$this->getId());
    }

    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        throw new \Exception("Missing processor ".$this->getId()." not found in the container");
    }
}