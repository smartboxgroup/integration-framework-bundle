<?php

namespace Smartbox\Integration\FrameworkBundle\Endpoints;


use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Producers\ProducerInterface;

interface EndpointInterface extends SerializableInterface, ConfigurableInterface{

    const OPTION_CONSUMER = '_consumer';
    const OPTION_PRODUCER = '_producer';
    const OPTION_CLASS = '_class';
    const OPTION_EXCHANGE_PATTERN = 'exchangePattern';
    const OPTION_TRACK = 'track';
    const OPTION_ENDPOINT_ROUTE = '_route';

    const EXCHANGE_PATTERN_IN_ONLY = 'inOnly';
    const EXCHANGE_PATTERN_IN_OUT = 'inOut';

    /**
     * @param string $resolvedUri
     * @param array $resolvedOptions
     */
    public function __construct($resolvedUri, array $resolvedOptions);

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
     * @return string
     */
    public function getExchangePattern();

    /**
     * @return bool
     */
    public function isInOnly();

    /**
     * @return bool
     */
    public function shouldTrack();

}