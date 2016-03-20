<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Producers;

use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class Producer
 * @package Smartbox\Integration\FrameworkBundle\Core\Producers
 */
abstract class Producer extends Service implements ProducerInterface
{
    /** {@inheritDoc} */
    public abstract function send(Exchange $ex, EndpointInterface $endpoint);
}