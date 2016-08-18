<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

/**
 * Trait FlowsVersionAware.
 */
trait FlowsVersionAware
{
    /** @var string */
    protected $flowsVersion;

    /**
     * @return string
     */
    public function getFlowsVersion()
    {
        return (string) $this->flowsVersion;
    }

    /**
     * @param mixed $flowsVersion
     */
    public function setFlowsVersion($flowsVersion)
    {
        $this->flowsVersion = $flowsVersion;
    }
}
