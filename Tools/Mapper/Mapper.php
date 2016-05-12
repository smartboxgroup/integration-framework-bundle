<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Mapper;

use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;

/**
 * Class Mapper
 */
class Mapper implements MapperInterface
{
    use UsesEvaluator;

    protected $mappings = [];

    protected $dictionary = [
        'ISO8601' => \DateTime::ISO8601,
        'ISO8601Micro' => 'Y-m-d\TH:i:s.000',
    ];

    public function addMappings(array $mappings)
    {
        foreach ($mappings as $mappingName => $mapping) {
            $this->mappings[$mappingName] = $mapping;
        }
    }

    /**
     * @param mixed  $obj
     * @param string $mappingName
     *
     * @return array|mixed
     */
    public function map($obj, $mappingName)
    {
        if (!$mappingName || !array_key_exists($mappingName, $this->mappings)) {
            throw new \InvalidArgumentException(sprintf('Invalid mapping name "%s"', $mappingName));
        }

        if (empty($obj)) {
            return $obj;
        }

        $mapping = @$this->mappings[$mappingName];

        $dictionary = array_merge($this->dictionary, ['obj' => $obj]);

        $res = [];
        foreach ($mapping as $key => $expression) {
            $res[$key] = $this->evaluator->evaluateWithVars($expression, $dictionary);
        }

        return $res;
    }

    /**
     * @param array  $elements
     * @param string $mappingName
     *
     * @return array
     */
    public function mapAll(array $elements, $mappingName)
    {
        if (empty($elements)) {
            return $elements;
        } else {
            $res = [];
            foreach ($elements as $key => $element) {
                $res[$key] = $this->map($element, $mappingName);
            }

            return $res;
        }
    }

    /**
     * Get the first element of an array.
     *
     * @param array $array
     * @return mixed
     */
    public function first(array $array)
    {
        return reset($array);
    }

    /**
     * Convert an string to date.
     *
     * @param string $date
     * @return \DateTime
     */
    public function stringToDate($date)
    {
        return new \DateTime($date);
    }

    /**
     * Create a Soap object var.
     *
     * @param mixed $data Data to create the SoapVar object
     * @param string $type Entity type name
     *
     * @return \SoapVar
     */
    public function toSoapObjVar($data, $type)
    {
        return new \SoapVar($data, SOAP_ENC_OBJECT, $type);
    }
}
