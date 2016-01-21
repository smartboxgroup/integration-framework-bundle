<?php

namespace Smartbox\Integration\FrameworkBundle\Processors;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasType;

class Itinerary implements SerializableInterface
{
    use HasType;

    /**
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @var string
     */
    protected $name;

    /** @var  Processor[] */
    protected $processors;

    public function __construct($name = null)
    {
        $this->name = $name;
        $this->processors = array();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     */
    public function setName($name){
        $this->name = $name;
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("processors")
     * @JMS\Type("array<Smartbox\Integration\FrameworkBundle\Processors\Itinerary>")
     * @JMS\Expose
     * @JMS\Groups({"metadata"})
     * @return array
     */
    public function getProcessorIds()
    {
        $arr = array();
        foreach ($this->processors as $processor) {
            $arr[] = $processor->getId();
        }

        return $arr;
    }

    /**
     * @return Processor[]
     */
    public function getProcessors()
    {
        return $this->processors;
    }

    /**
     * @param Processor[] $processors
     */
    public function setProcessors(array $processors)
    {
        $this->processors = $processors;
    }

    /**
     * @param Processor $processor
     */
    public function addProcessor(Processor $processor)
    {
        $this->processors[] = $processor;
    }

    public function prepend(Itinerary $itinerary)
    {
        $this->processors = array_merge($itinerary->processors, $this->processors);
    }

    public function append(Itinerary $itinerary)
    {
        $this->processors = array_merge($this->processors, $itinerary->processors);
    }

    /**
     * @return Processor
     */
    public function shiftProcessor()
    {
        return array_shift($this->processors);
    }

    /**
     * @param Processor $processor
     */
    public function unShiftProcessor(Processor $processor){
        array_unshift($this->processors,$processor);
    }
}