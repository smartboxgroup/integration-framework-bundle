<?php
namespace Smartbox\Integration\FrameworkBundle\Producers;


use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;

interface ProducerInterface extends SerializableInterface{

    /**
     * Sends an exchange to the producer
     *
     * @param Exchange $ex
     * @throws \Exception
     */
    public function send(Exchange $ex, EndpointInterface $endpoint);

}