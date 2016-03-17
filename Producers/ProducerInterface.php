<?php
namespace Smartbox\Integration\FrameworkBundle\Producers;


use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;

interface ProducerInterface extends SerializableInterface{

    /**
     * Sends an exchange to the producer
     *
     * @param Exchange $ex
     * @throws \Exception
     */
    public function send(Exchange $ex, array $options);

    /**
     * Validates the options passed to an producer
     *
     * @param array $options
     * @throws InvalidOptionException in case one of the options is not valid
     */
    public static function validateOptions(array $options, $checkComplete = false);

    /**
     * Get default options
     *
     * @return array
     */
    function getDefaultOptions();

    /**
     * Get default options
     *
     * @return array Array with option name, description, and options (optional)
     */
    function getAvailableOptions();
}