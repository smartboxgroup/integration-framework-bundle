<?php

namespace Smartbox\Integration\FrameworkBundle\Consumers;


interface ConsumerInterface {
    /**
     * @return mixed
     */
    public function stop();

    /**
     * @param $count
     */
    public function setExpirationCount($count);

    /**
     * @return int
     */
    public function getExpirationCount();

    /**
     * @return bool
     */
    public function shouldStop();

    /**
     * @param array $endpointOptions
     */
    public function consume($endpointOptions);
}