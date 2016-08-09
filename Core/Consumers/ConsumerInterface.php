<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Consumers;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\ServiceInterface;
use Smartbox\Integration\FrameworkBundle\Tools\Helper\SmartesbHelper;

/**
 * Interface ConsumerInterface
 */
interface ConsumerInterface extends ServiceInterface
{
    /**
     * Signal the consumer to stop before processing the next message.
     */
    public function stop();

    /**
     * @param $count
     */
    public function setExpirationCount($count);

    /**
     * Consumes messages from the given $endpoint until either the expirationCount reaches 0 or ::stop() is called.
     *
     * @param EndpointInterface $endpoint
     *
     * @return bool
     */
    public function consume(EndpointInterface $endpoint);

    /**
     * @param SmartesbHelper $helper
     *
     * @return mixed
     */
    public function setSmartesbHelper(SmartesbHelper $helper = null);

    /**
     * Get a descriptive name of the producer
     * @return string
     */
    public function getName();
}
