<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Itinerary;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;

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
    protected $processorIds = [];

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
    public function getProcessorIds()
    {
        return $this->processorIds;
    }

    /**
     * @param string[] $processorIds
     */
    public function setProcessorIds(array $processorIds)
    {
        $this->processorIds = $processorIds;
    }

    /**
     * @param string $processorId
     */
    public function addProcessorId($processorId)
    {
        if (!is_string($processorId)) {
            throw new \InvalidArgumentException('addProcessorId argument expected to be a string.');
        }
        $this->processorIds[] = $processorId;
    }

    public function prepend(Itinerary $itinerary)
    {
        $this->processorIds = array_merge($itinerary->processorIds, $this->processorIds);
    }

    public function append(Itinerary $itinerary)
    {
        $this->processorIds = array_merge($this->processorIds, $itinerary->processorIds);
    }

    /**
     * @return string
     */
    public function shiftProcessorId()
    {
        return array_shift($this->processorIds);
    }

    /**
     * @param string $processorId
     */
    public function unShiftProcessorId($processorId)
    {
        if (!is_string($processorId)) {
            throw new \InvalidArgumentException('unShiftProcessorId argument expected to be a string.');
        }
        array_unshift($this->processorIds, $processorId);
    }

    public function getCount(){
        return count($this->processors);
    }
}
