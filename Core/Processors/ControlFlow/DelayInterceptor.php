<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\DelayException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ThrottledException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions\ThrottlingLimitReachedException;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;

class DelayInterceptor extends Processor
{
    protected $delayPeriod = 0;

    /**
     * @return bool
     */
    public function isRuntimeBreakpoint()
    {
        return $this->runtimeBreakpoint;
    }

    /**
     * @param bool $runtimeBreakpoint
     */
    public function setRuntimeBreakpoint($runtimeBreakpoint)
    {
        $this->runtimeBreakpoint = $runtimeBreakpoint;
    }

    /**
     * @return int
     */
    public function getDelayPeriod()
    {
        return $this->delayPeriod;
    }

    /**
     * @param int $periodMs
     */
    public function setDelayPeriod(int $delayPeriod)
    {
        $this->delayPeriod = $delayPeriod;
    }

    /**
     * @param Exchange          $exchange
     * @param SerializableArray $processingContext
     *
     * @throws ThrottlingLimitReachedException
     * @throws ThrottledException
     */
    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        throw new DelayException();
    }
}
