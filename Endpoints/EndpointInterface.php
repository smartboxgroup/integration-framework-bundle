<?php

namespace Smartbox\Integration\FrameworkBundle\Endpoints;


use Smartbox\Integration\FrameworkBundle\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Processors\ProcessorInterface;
use Smartbox\Integration\FrameworkBundle\Producers\ProducerInterface;

interface EndpointInterface {

    const OPTION_CONSUMER = '_consumer';
    const OPTION_PRODUCER = '_consumer';
    const OPTION_CLASS = '_class';

    /**
     * @param string $resolvedUri
     * @param array $resolvedEndpointOptions
     */
    public function __construct($resolvedUri, array $resolvedEndpointOptions);

    /**
     * Returns the resolved URI
     * @return string
     */
    public function getURI();

    /**
     * @return ConsumerInterface
     */
    public function getConsumer();

    /**
     * @return ProducerInterface
     */
    public function getProducer();

    /**
     * @return MessageInterface
     */
    public function consume();

    /**
     * @return boolean
     */
    public function produce(Exchange $exchange);

    /**
     * @return array
     */
    public function getOptions();

    /**
     * Validates the options passed to an endpoint
     *
     * @param array $options
     * @throws InvalidOptionException in case one of the options is not valid
     */
    public static function validateOptions(array $options, $checkComplete = false);

    /**
     * Get static default options
     *
     * @return array
     */
    public static function getDefaultOptions();

    /**
     * Get static default options
     *
     * @return array Array with option name, description, and options (optional)
     */
    public static function getAvailableOptions();

}