<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Protocols;


use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerInterface;

interface ProtocolInterface extends ConfigurableInterface {
    /**
     * @param ConsumerInterface $consumer
     * @return void
     */
    public function setDefaultConsumer(ConsumerInterface $consumer);

    /**
     * @param ProducerInterface $producer
     * @return void
     */
    public function setDefaultProducer(ProducerInterface $producer);

    /**
     * @param HandlerInterface $handler
     * @return void
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