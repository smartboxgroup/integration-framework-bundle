<?php
namespace Smartbox\Integration\FrameworkBundle\Connectors;


use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;

interface ConnectorInterface extends SerializableInterface{

    /**
     * Sends an exchange to the connector
     *
     * @param Exchange $ex
     * @throws \Exception
     */
    public function send(Exchange $ex, array $options);

    /**
     * Validates the options passed to an connector
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