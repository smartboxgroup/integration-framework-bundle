<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Serializables;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasType;
use Symfony\Component\Validator\Constraints as Assert;
use Smartbox\CoreBundle\Type\Entity;

class SimpleObject implements SerializableInterface
{
    use HasType;

    /**
     * @JMS\Type("integer")
     * @JMS\Expose
     * @var int
     */
    protected $integerValue;

    /**
     * @JMS\Type("double")
     * @JMS\Expose
     * @var double
     */
    protected $doubleValue;

    /**
     * @JMS\Type("string")
     * @JMS\Expose
     * @var string
     */
    protected $stringValue;

    /**
     * @var Entity
     * @JMS\Type("Smartbox\CoreBundle\Tests\Fixtures\Entity\TestEntity")
     * @JMS\Expose
     */
    protected $nestedEntity;

    /**
     * @JMS\Type("array<Smartbox\CoreBundle\Tests\Fixtures\Entity\TestEntity>")
     * @JMS\Expose
     * @var \Smartbox\CoreBundle\Tests\Fixtures\Entity\TestEntity[]
     */
    protected $arrayOfEntities = [];

    /**
     * @JMS\Type("array<Smartbox\CoreBundle\Type\Integer>")
     * @JMS\Expose
     * @var \Smartbox\CoreBundle\Type\Integer[]
     */
    protected $arrayOfIntegers = [];

    /**
     * @JMS\Type("array<Smartbox\CoreBundle\Type\String>")
     * @JMS\Expose
     * @var \Smartbox\CoreBundle\Type\String[]
     */
    protected $arrayOfStrings = [];

    /**
     * @JMS\Type("array<Smartbox\CoreBundle\Type\Double>")
     * @JMS\Expose
     * @var \Smartbox\CoreBundle\Type\Double[]
     */
    protected $arrayOfDoubles = [];

    /**
     * @JMS\Type("array<Smartbox\CoreBundle\Type\Date>")
     * @JMS\Expose
     * @var \Smartbox\CoreBundle\Type\Date[]
     */
    protected $arrayOfDates = [];

    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getIntegerValue()
    {
        return $this->integerValue;
    }

    /**
     * @param int $integerValue
     */
    public function setIntegerValue($integerValue)
    {
        $this->integerValue = $integerValue;
    }

    /**
     * @return float
     */
    public function getDoubleValue()
    {
        return $this->doubleValue;
    }

    /**
     * @param float $doubleValue
     */
    public function setDoubleValue($doubleValue)
    {
        $this->doubleValue = $doubleValue;
    }

    /**
     * @return string
     */
    public function getStringValue()
    {
        return $this->stringValue;
    }

    /**
     * @param string $stringValue
     */
    public function setStringValue($stringValue)
    {
        $this->stringValue = $stringValue;
    }

    /**
     * @return Entity
     */
    public function getNestedEntity()
    {
        return $this->nestedEntity;
    }

    /**
     * @param Entity $nestedEntity
     */
    public function setNestedEntity($nestedEntity)
    {
        $this->nestedEntity = $nestedEntity;
    }

    /**
     * @return \Smartbox\CoreBundle\Tests\Fixtures\Entity\TestEntity[]
     */
    public function getArrayOfEntities()
    {
        return $this->arrayOfEntities;
    }

    /**
     * @param \Smartbox\CoreBundle\Tests\Fixtures\Entity\TestEntity[] $arrayOfEntities
     */
    public function setArrayOfEntities($arrayOfEntities)
    {
        $this->arrayOfEntities = $arrayOfEntities;
    }

    /**
     * @return \Smartbox\CoreBundle\Type\Integer[]
     */
    public function getArrayOfIntegers()
    {
        return $this->arrayOfIntegers;
    }

    /**
     * @param \Smartbox\CoreBundle\Type\Integer[] $arrayOfIntegers
     */
    public function setArrayOfIntegers($arrayOfIntegers)
    {
        $this->arrayOfIntegers = $arrayOfIntegers;
    }

    /**
     * @return \Smartbox\CoreBundle\Type\String[]
     */
    public function getArrayOfStrings()
    {
        return $this->arrayOfStrings;
    }

    /**
     * @param \Smartbox\CoreBundle\Type\String[] $arrayOfStrings
     */
    public function setArrayOfStrings($arrayOfStrings)
    {
        $this->arrayOfStrings = $arrayOfStrings;
    }

    /**
     * @return \Smartbox\CoreBundle\Type\Double[]
     */
    public function getArrayOfDoubles()
    {
        return $this->arrayOfDoubles;
    }

    /**
     * @param \Smartbox\CoreBundle\Type\Double[] $arrayOfDoubles
     */
    public function setArrayOfDoubles($arrayOfDoubles)
    {
        $this->arrayOfDoubles = $arrayOfDoubles;
    }

    /**
     * @return \Smartbox\CoreBundle\Type\Date[]
     */
    public function getArrayOfDates()
    {
        return $this->arrayOfDates;
    }

    /**
     * @param \Smartbox\CoreBundle\Type\Date[] $arrayOfDates
     */
    public function setArrayOfDates($arrayOfDates)
    {
        $this->arrayOfDates = $arrayOfDates;
    }
}