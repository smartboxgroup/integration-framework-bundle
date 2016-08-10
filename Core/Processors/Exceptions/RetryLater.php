<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions;


class RetryLater extends \RuntimeException{

    protected $delay;

    /**
     * @return mixed
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * @param mixed $delay
     */
    public function setDelay($delay)
    {
        $this->delay = $delay;
    }

}