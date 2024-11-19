<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\Mapper;

use JMS\Serializer\SerializationContext;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\Tools\Helper\DateTimeHelper;

/**
 * Class Mapper.
 */
class Mapper implements MapperInterface
{
    use UsesEvaluator;

    protected $debug = false;

    protected $mappings = [];

    protected $dictionary = [
        'ISO8601' => \DateTime::ISO8601,
        'ISO8601Micro' => 'Y-m-d\TH:i:s.000',
    ];

    public function setDebug($debug = false)
    {
        $this->debug = $debug;
    }

    public function addMappings(array $mappings)
    {
        foreach ($mappings as $mappingName => $mapping) {
            $this->mappings[$mappingName] = $mapping;
        }
    }

    /**
     * @param $format
     * @param \DateTime|null $date
     *
     * @return null|string
     */
    public function formatDate($format, \DateTime $date = null)
    {
        if (null === $date) {
            return null;
        }

        return $date->format($format);
    }

    /**
     * Format the date to ISO8601 format "combined date and time in UTC". Ex: 2018-05-30T13:07:26Z.
     *
     * @param \DateTime|null $date
     *
     * @return null|string
     */
    public function formatDateTimeUtc(\DateTime $date = null)
    {
        if (null === $date) {
            return null;
        }

        $date->setTimezone(new \DateTimeZone('UTC'));

        return $date->format("Y-m-d\TH:i:s\Z");
    }

    /**
     * @param mixed  $obj
     * @param string $mappingName
     * @param mixed  $context
     *
     * @return array|mixed
     */
    public function map($obj, $mappingName, $context = [])
    {
        if (!$mappingName || !array_key_exists($mappingName, $this->mappings)) {
            throw new \InvalidArgumentException(sprintf('Invalid mapping name "%s"', $mappingName));
        }

        if (empty($obj) && !$this->shouldAcceptEmptyValues($context, $obj)) {
            return $obj;
        }

        $mapping = @$this->mappings[$mappingName];

        return $this->resolve($mapping, $obj, $context);
    }

    /**
     * Merge multiples arrays values in a single mappingName
     *
     * @param array $elements
     * @param string $mappingName
     * @param array<array{mapFunction: string, object: array|null, context: string}> $mappers
     *
     * @return null|array
     */
    public function mergeMapping($elements, $mappingName, $mappers)
    {
        if(empty($elements)) {
            return null;
        }

        if (empty($mappers)) {
            throw new \InvalidArgumentException('No mapper provided.');
        }

        $response = [];

        foreach ($mappers as $mapper){
            $object = $this->getObject($elements, $mapper[1]);
            if(empty($object)){
                continue;
            }

            $mapFunction = $mapper[0] ?? null;
            $context = $mapper[2] ?? $mapper[1] ?? null;

            if(empty($mapFunction) || empty($context) || !in_array($mapFunction, ['map', 'mapAll'])){
                throw new \InvalidArgumentException('No mapper function or context informed');
            }

            $mappedObject = $this->$mapFunction($object, $mappingName, $context);
            if (!empty($mappedObject)) {
                $response = ($mapFunction === 'map')
                    ? array_merge($response, [$mappedObject])
                    : array_merge($response, $mappedObject);
            }
        }

        return $response;
    }


    private function getObject(array $elements, string $path) {
        $keys = explode('.', $path);
        $current = $elements;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * @param $mapping
     * @param $obj
     * @param $context
     *
     * @return array|string|null
     *
     * @throws \Exception
     */
    public function resolve(&$mapping, &$obj, &$context)
    {
        if (empty($mapping)) {
            return $mapping;
        } elseif (is_array($mapping)) {
            $res = [];
            foreach ($mapping as $key => $value) {
                $resolved = $this->resolve($value, $obj, $context);

                if (null !== $resolved) {
                    $res[$key] = $resolved;
                }
            }

            return $res;
        } elseif (is_string($mapping)) {
            $dictionary = array_merge($this->dictionary, ['obj' => $obj, 'context' => $context]);
            $res = null;

            try {
                $res = @$this->evaluator->evaluateWithVars($mapping, $dictionary);
            } catch (\RuntimeException $e) {
                if ($this->debug) {
                    throw $e;
                }
            }

            return $res;
        }

        throw new \RuntimeException('Mapper expected the mapping to be a string or an array');
    }

    /**
     * @param array  $elements
     * @param string $mappingName
     * @param array  $context
     * @param bool   $disassociate
     *
     * @return array|mixed
     */
    public function mapAll($elements, $mappingName, $context = [], $disassociate = false)
    {
        if (!is_array($elements)) {
            throw new \RuntimeException('MapAll expected an array');
        }

        if (empty($elements)) {
            return $elements;
        }

        $res = [];
        foreach ($elements as $key => $element) {
            $element = ($disassociate) ? ['key' => $key, 'value' => $element] : $element;
            $res[$key] = $this->map($element, $mappingName, $context);
        }

        return $res;
    }

    /**
     * Return true if the key exists in the given array.
     *
     * @param array  $obj
     * @param string $key
     *
     * @return bool
     */
    public function keyExists(array $obj, $key)
    {
        return array_key_exists($key, $obj);
    }

    /**
     * @param $class
     * @param $property
     *
     * @return bool
     */
    public function propertyExists($class, $property)
    {
        return property_exists($class, $property);
    }

    /**
     * Get the first element of an array.
     *
     * @param array $array
     *
     * @return mixed
     */
    public function first(array $array)
    {
        return reset($array);
    }

    /**
     * Convert a string to date.
     *
     * @param string $date
     *
     * @return \DateTime
     */
    public function stringToDate($date)
    {
        if (!empty($date)) {
            return new \DateTime($date);
        }
    }

    public function timestampToDate($timestamp)
    {
        $date = new \DateTime();
        $date->setTimestamp($timestamp);

        return $date;
    }

    /**
     * Convert a formatted string to a date.
     *
     * The portions of the generated time not provided in format, will be set to corresponding values from the Unix epoch.
     * Ex: dateFromFormat('d/m/Y', '23/03/2018').
     *
     * @param string $format
     * @param string $date
     *
     * @return bool|\DateTime
     */
    public function dateFromFormat($format, $date)
    {
        if (!empty($format) && !empty($date)) {
            return \DateTime::createFromFormat($format, $date);
        }
    }

    /**
     * @param $timestamp
     *
     * @return \DateTime
     */
    public function timestampWithMsToDate($timestamp)
    {
        return DateTimeHelper::createDateTimeFromTimestampWithMilliseconds($timestamp);
    }

    /**
     * Create a Soap var object.
     *
     * @param mixed  $data     Data to create the SoapVar object
     * @param string $encoding The encoding id
     * @param string $type     Entity type name
     *
     * @return \SoapVar
     */
    public function toSoapVarObj($data, $encoding, $type = null)
    {
        return new \SoapVar($data, $encoding, $type);
    }

    /**
     * Transform string only to \SoapVar
     *
     * @param $item
     * @param $key
     */
    protected function transformToSoapVar(&$item, $key)
    {
        if (is_string($item)) {
            $item = static::toSoapVarObj($item, constant('XSD_STRING'));
        }
    }

    /**
     * Traverse array and convert all string data to \SoapVar so we ensure we escape special characters.
     *
     * @param array $data
     *
     * @return array
     */
    public function arrayToSoapVars(array $data)
    {
        array_walk_recursive($data, [$this, 'transformToSoapVar']);
        
        return $data;
    }

    /**
     * Convert an array into string.
     *
     * @param string $glue The string to use to glue the elements of the array
     * @param array  $data The array of strings to join
     *
     * @return string
     */
    public function arrayToString($glue, array $data)
    {
        return implode($glue, $data);
    }

    /**
     * Converts a string into an array.
     *
     * @param string $delimiter
     * @param string $string
     *
     * @return array
     */
    public function stringToArray($delimiter, $string)
    {
        return explode($delimiter, $string);
    }

    /**
     * Flatten an array by key.
     *
     * @param array  $data The array to flatten
     * @param string $key  The common key
     *
     * @return array
     */
    public function flattenArrayByKey(array $data, $key)
    {
        $array = [];
        foreach ($data as $value) {
            if (is_array($value) && isset($value[$key])) {
                $array[] = $value[$key];
            }
        }

        return $array;
    }

    public function toString($data)
    {
        return (string) $data;
    }

    public function toMongoID($id)
    {
        if (class_exists('\MongoDB\BSON\ObjectID')) {
            return new \MongoDB\BSON\ObjectID((string) $id);
        }

        throw new \RuntimeException('To instantiate a Mongo ObjectID object you need to install the php mongo extension.');
    }

    /**
     * @param $search
     * @param $replace
     * @param $subject
     *
     * @return mixed
     */
    public function replaceStr($search, $replace, $subject)
    {
        return str_replace($search, $replace, $subject);
    }

    /**
     * Serialize the given datas into the expected format with a group.
     *
     * @param $data
     * @param $format
     * @param $group
     *
     * @return string
     */
    public function serializeWithGroup($data, $format, $group)
    {
        $serializer = $this->evaluator->getSerializer();

        return $serializer->serialize($data, $format, SerializationContext::create()->setGroups([$group]));
    }

    /**
     * Return the n-th section of the given string splitted by piece of the given length.
     *
     * @param string $string
     * @param int    $length
     * @param int    $section
     *
     * @return string
     */
    public function wordWrap($string, $length, $section)
    {
        $wrapped = wordwrap($string, $length, '\mapperDelimiter', true);
        $lines = explode('\mapperDelimiter', $wrapped);

        --$section;
        $result = '';
        if (isset($lines[$section])) {
            $result = $lines[$section];
        }

        return $result;
    }

    /**
     * Return $value if $string is empty. $value can be null. If $string is not empty, returns $string.
     *
     * @param string $string
     * @param string $value
     *
     * @return string
     */
    public function emptyTo($string, $value)
    {
        if (!empty($string)) {
            return $string;
        } else {
            return $value;
        }
    }

    /**
     * Will check if a value match a specific type, and if so will still map it. Ex:
     * - `allow_empty_string => true` will allow ''
     * - `allow_empty_numeric => true` will allow 0
     *
     * @param array $context
     * @param mixed $obj
     *
     * @return bool
     */
    protected function shouldAcceptEmptyValues(array $context, $obj): bool
    {
        foreach ($context as $key => $val) {
            if (preg_match('/^allow_empty_(\w+)$/', $key, $matches) && $val) {
                return (bool) call_user_func("is_{$matches[1]}", $obj);
            }
        }

        return false;
    }
}
