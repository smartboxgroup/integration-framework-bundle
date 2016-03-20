<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Consumers;


use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Tools\Helper\SmartesbHelper;

interface ConsumerInterface {
    /**
     * @return void
     */
    public function stop();

    /**
     * @param $count
     * @return void
     */
    public function setExpirationCount($count);

    /**
     * Consumes messages from the given $endpoint until either the expirationCount reaches 0 or ::stop() is called
     *
     * @param EndpointInterface $endpoint
     * @return boolean
     */
    public function consume(EndpointInterface $endpoint);

    /**
     * @param SmartesbHelper $helper
     * @return mixed
     */
    public function setSmartesbHelper(SmartesbHelper $helper = null);
}