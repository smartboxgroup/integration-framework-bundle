<?php

namespace Smartbox\Integration\FrameworkBundle\Events;


class TimingEvent extends Event
{
    const CONSUMER_TIMING = 'smartesb.consumer.timing';

    /**
     * @var integer
     */
    private $intervalMs;

    /**
     * @return integer
     */
    public function getIntervalMs()
    {
        return $this->intervalMs;
    }

    /**
     * @param integer $intervalMs
     */
    public function setIntervalMs($intervalMs)
    {
        $this->intervalMs = $intervalMs;
    }
}