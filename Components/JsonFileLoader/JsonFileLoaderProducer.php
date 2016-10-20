<?php

namespace Smartbox\Integration\FrameworkBundle\Components\JsonFileLoader;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Exceptions\Deprecated\InvalidFormatException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Class JsonFileLoaderProducer.
 */
class JsonFileLoaderProducer extends Producer
{
    const OPTION_FILENAME = 'filename';
    const OPTION_BASE_PATH = 'base_path';

    use UsesSerializer;

    /** {@inheritdoc} */
    public function send(Exchange $ex, EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        $path = $options[self::OPTION_BASE_PATH].'/'.$options[self::OPTION_FILENAME];

        if (!file_exists($path)) {
            $path = $path.'.json';
        }

        if (!file_exists($path)) {
            throw new FileNotFoundException("Json file not found in $path");
        }

        $json = @file_get_contents($path);

        if (empty($json) || !$this->isJson($json)) {
            throw new InvalidFormatException("The file $path does not have a valid JSON format");
        }

        $serializer = $this->getSerializer();

        $data = trim(file_get_contents($path));
        if (substr($data, 0, 1) === '[') { // Tests if the current content is an array
            $deserializationType = 'array<'.SerializableInterface::class.'>';
            $array = $serializer->deserialize($data, $deserializationType, 'json');
            $content = new SerializableArray($array);
        } else {
            $content = $serializer->deserialize($data, SerializableInterface::class, 'json');
        }

        $ex->getIn()->setBody($content);
    }

    /**
     * Checks if a string is a JSON file.
     *
     * @param $string
     *
     * @return bool
     */
    private function isJson($string)
    {
        json_decode($string);

        return json_last_error() == JSON_ERROR_NONE;
    }
}
