<?php

namespace Smartbox\Integration\FrameworkBundle\Producers;

use Smartbox\Integration\FrameworkBundle\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class Producer
 * @package Smartbox\Integration\FrameworkBundle\Producers
 */
abstract class Producer extends Service implements ProducerInterface
{
    /** {@inheritDoc} */
    public abstract function send(Exchange $ex, EndpointInterface $endpoint);
}