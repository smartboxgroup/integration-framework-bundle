<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Protocols;

use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerInterface;

/**
 * Interface ProtocolInterface.
 */
interface ProtocolInterface extends ConfigurableInterface
{
    /**
     * @param ConsumerInterface $consumer
     */
    public function setDefaultConsumer(ConsumerInterface $consumer);

    /**
     * @param ProducerInterface $producer
     */
    public function setDefaultProducer(ProducerInterface $producer);

    /**
     * @param HandlerInterface $handler
     */
    public function setDefaultHandler(HandlerInterface $handler);

    /**
     * @return HandlerInterface
     */
    public function getDefaultHandler();

    /**
     * @return ConsumerInterface
     */
    public function getDefaultConsumer();

    /**
     * @return ProducerInterface
     */
    public function getDefaultProducer();
}
