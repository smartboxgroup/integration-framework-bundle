<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Endpoints;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\ProtocolInterface;

/**
 * Interface EndpointInterface.
 */
interface EndpointInterface extends SerializableInterface
{
    /**
     * @param $resolvedUri
     * @param array             $resolvedOptions
     * @param ProtocolInterface $protocol
     * @param ProducerInterface $producer
     * @param ConsumerInterface $consumer
     * @param HandlerInterface  $handler
     */
    public function __construct(
        $resolvedUri,
        array &$resolvedOptions,
        ProtocolInterface $protocol,
        ProducerInterface $producer = null,
        ConsumerInterface $consumer = null,
        HandlerInterface $handler = null
    );

    /**
     * Returns the resolved URI.
     *
     * @return string
     */
    public function getURI();

    /**
     * @return ProtocolInterface
     */
    public function getProtocol();

    /**
     * @return HandlerInterface
     */
    public function getHandler();

    /**
     * @return ConsumerInterface
     */
    public function getConsumer();

    /**
     * @return ProducerInterface
     */
    public function getProducer();

    /**
     * Consumes $maxAmount of messages, if $maxAmount is 0, then it consumes indefinitely in a loop.
     *
     * @param int $maxAmount
     */
    public function consume($maxAmount = 0);

    /**
     * @param Exchange $exchange
     */
    public function produce(Exchange $exchange);

    /**
     * @param MessageInterface $message
     *
     * @return MessageInterface
     */
    public function handle(MessageInterface $message);

    /**
     * @return array
     */
    public function getOptions();

    /**
     * @param string $optionName
     *
     * @return mixed
     */
    public function getOption($optionName);

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
