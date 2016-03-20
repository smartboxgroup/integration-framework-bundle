<?php

namespace Smartbox\Integration\FrameworkBundle\Components\JsonFileLoader;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Exceptions\Deprecated\InvalidFormatException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class JsonFileLoaderProducer
 * @package Smartbox\Integration\FrameworkBundle\Core\Producers
 */
class JsonFileLoaderProducer extends Producer implements ConfigurableInterface
{
    const OPTION_FILENAME = 'filename';
    const OPTION_BASE_PATH = 'base_path';

    use UsesSerializer;

    /** {@inheritdoc} */
    public function send(Exchange $ex, EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
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

        $ex->getIn()->setBody($content);
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

    /**
     *  Key-Value array with the option name as key and the details as value
     *
     *  [OptionName => [description, array of valid values],..]
     *
     * @return array
     */
    public function getOptionsDescriptions()
    {
        $options = array(
            self::OPTION_BASE_PATH => array('Base path to look for the json file', array()),
            self::OPTION_FILENAME => array('Name of the file to load', array()),
        );

        return $options;
    }

    /**
     * With this method this class can configure an OptionsResolver that will be used to validate the options
     *
     * @param OptionsResolver $resolver
     * @return mixed
     */
    public function configureOptionsResolver(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::OPTION_BASE_PATH);
        $resolver->setAllowedTypes(self::OPTION_BASE_PATH,['string']);
        $resolver->setRequired(self::OPTION_FILENAME);
        $resolver->setAllowedTypes(self::OPTION_FILENAME,['string']);
    }
}