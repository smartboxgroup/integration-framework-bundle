<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Producers;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class Producer.
 */
abstract class Producer extends Service implements ProducerInterface
{
    /** {@inheritdoc} */
    abstract public function send(Exchange $ex, EndpointInterface $endpoint);

    /** {@inheritdoc} */
    public function getName()
    {
        $reflection = new \ReflectionClass(self::class);
        $name = $reflection->getShortName();
        
        return basename($name, 'Producer');
    }
}
