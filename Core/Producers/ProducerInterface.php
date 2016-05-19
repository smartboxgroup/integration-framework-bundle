<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Producers;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\ServiceInterface;

/**
 * Interface ProducerInterface.
 */
interface ProducerInterface extends ServiceInterface
{
    /**
     * Sends an exchange to the producer.
     *
     * @param \Smartbox\Integration\FrameworkBundle\Core\Exchange $ex
     *
     * @throws \Exception
     */
    public function send(Exchange $ex, EndpointInterface $endpoint);

    /**
     * Get a descriptive name of the producer
     * @return string
     */
    public function getName();
}
