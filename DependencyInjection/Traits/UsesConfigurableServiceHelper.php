<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;


use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableServiceHelper;

trait UsesConfigurableServiceHelper {

    /** @var  ConfigurableServiceHelper */
    protected $configurableServiceHelper;

    /**
     * @return ConfigurableServiceHelper
     */
    public function getConfigurableServiceHelper()
    {
        return $this->configurableServiceHelper;
    }

    /**
     * @param ConfigurableServiceHelper $configurableServiceHelper
     */
    public function setConfigurableServiceHelper($configurableServiceHelper)
    {
        $this->configurableServiceHelper = $configurableServiceHelper;
    }
}