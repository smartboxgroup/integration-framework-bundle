<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableServiceHelper;

trait UsesConfigurableServiceHelper
{
    /** @var  ConfigurableServiceHelper */
    protected $confHelper;

    /**
     * @return ConfigurableServiceHelper
     */
    public function getConfHelper()
    {
        return $this->confHelper;
    }

    /**
     * @param ConfigurableServiceHelper $confHelper
     */
    public function setConfHelper($confHelper)
    {
        $this->confHelper = $confHelper;
    }
}
