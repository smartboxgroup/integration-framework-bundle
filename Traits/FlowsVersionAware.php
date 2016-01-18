<?php
namespace Smartbox\Integration\FrameworkBundle\Traits;


trait FlowsVersionAware {

    protected $flowsVersion;

    /**
     * @return mixed
     */
    public function getFlowsVersion()
    {
        return $this->flowsVersion;
    }

    /**
     * @param mixed $flowsVersion
     */
    public function setFlowsVersion($flowsVersion)
    {
        $this->flowsVersion = $flowsVersion;
    }

}