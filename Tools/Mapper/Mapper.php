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
     * @param $format
     * @param \DateTime|null $date
     * @return null|string
     */
    public function formatDate($format, \DateTime $date = null){
        if ($date === null) {
            return null;
        }

        return $date->format($format);
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
            $value = $this->evaluator->evaluateWithVars($expression, $dictionary);
            if($value !== null){
                $res[$key] = $value;
            }
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
        }

        $res = [];
        foreach ($elements as $key => $element) {
            $res[$key] = $this->map($element, $mappingName);
        }

        return $res;
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
     * Create a Soap var object.
     *
     * @param mixed $data Data to create the SoapVar object
     * @param string $encoding The encoding id
     * @param string $type Entity type name
     *
     * @return \SoapVar
     */
    public function toSoapVarObj($data, $encoding, $type = null)
    {
        return new \SoapVar($data, $encoding, $type);
    }

    /**
     * Convert an array into string.
     *
     * @param string $glue The string to use to glue the elements of the array
     * @param array $data The array of strings to join
     *
     * @return string
     */
    public function arrayToString($glue, array $data)
    {
        return join($glue, $data);
    }
}