<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions;

class RetryLaterException extends \RuntimeException
{
    protected $delay;

    /**
     * @return int Delay in seconds
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * @param int $delay Delay in seconds
     */
    public function setDelay($delay)
    {
        $this->delay = $delay;
    }
}
