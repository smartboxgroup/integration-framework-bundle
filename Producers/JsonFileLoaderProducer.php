<?php

namespace Smartbox\Integration\FrameworkBundle\Producers;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidFormatException;
use Smartbox\Integration\FrameworkBundle\Traits\UsesSerializer;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use JMS\Serializer\Annotation as JMS;

/**
 * Class JsonFileLoaderProducer
 * @package Smartbox\Integration\FrameworkBundle\Producers
 */
class JsonFileLoaderProducer extends APIProducer
{
    const OPTION_FILENAME = 'filename';
    const OPTION_BASE_PATH = 'base_path';

    use UsesSerializer;

    /** {@inheritdoc} */
    protected function execute($entity, array $options)
    {
        $path = $options[self::OPTION_BASE_PATH] .'/'. $options[self::OPTION_FILENAME];

        if (!file_exists($path)) {
            $path = $path . '.json';
        }

        if (!file_exists($path)) {
            throw new FileNotFoundException("Json file not found in $path");
        }

        $json = @file_get_contents($path);

        if (empty($json) || !$this->isJson($json)) {
            throw new InvalidFormatException("The file $path does not have a valid JSON format");
        }

        $serializer = $this->getSerializer();
        $content = $serializer->deserialize($json, SerializableInterface::class, 'json');

        return $content;
    }

    /** {@inheritdoc} */
    protected function translateFromCanonical(SerializableInterface $entity = null, array $options)
    {
        return $entity;
    }

    /** {@inheritdoc} */
    protected function translateToCanonical($data, array $options)
    {
        return $data;
    }

    /**
     * Checks if a string is a JSON file
     * @param $string
     * @return bool
     */
    private function isJson($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }


    public function getAvailableOptions(){
        $options = array(
            self::OPTION_BASE_PATH => array('Base path to look for the json file', array()),
            self::OPTION_FILENAME => array('Name of the file to load', array()),
        );

        return $options;
    }
}