<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Itinerary;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;

/**
 * Class Itinerary.
 */
class Itinerary implements SerializableInterface
{
    use HasInternalType;

    /**
     * @JMS\Type("string")
     * @JMS\SerializedName("name")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     *
     * @var string
     */
    protected $name;

    /**
     * @JMS\Type("array<string>")
     * @JMS\SerializedName("processors")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     *
     * @var string
     */
    protected $processors = [];

    /**
     * Itinerary constructor.
     *
     * @param string|null $name
     */
    public function __construct($name = null)
    {
        $this->name = $name;
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
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string[]
     */
    public function getProcessors()
    {
        return $this->processors;
    }

    /**
     * @param string[] $processors
     */
    public function setProcessors(array $processors)
    {
        $this->processors = $processors;
    }

    /**
     * @param string $processor
     */
    public function addProcessor($processor)
    {
        if(!is_string($processor)){
            throw new \InvalidArgumentException("addProcessor first argument expected to be a string.");
        }
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
     * @return string
     */
    public function shiftProcessor()
    {
        return array_shift($this->processors);
    }

    /**
     * @param string $processor
     */
    public function unShiftProcessor($processor)
    {
        if(!is_string($processor)){
            throw new \InvalidArgumentException("unShiftProcessor first argument expected to be a string.");
        }
        array_unshift($this->processors, $processor);
    }
}
