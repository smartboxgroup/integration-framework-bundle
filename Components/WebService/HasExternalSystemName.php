<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService;

use JMS\Serializer\Annotation as JMS;

/**
 * Trait HasExternalSystem
 */
trait HasExternalSystemName
{
    /**
     * @var string
     * @JMS\Expose
     * @JMS\Type("string")
     * @JMS\SerializedName("requestHeaders")
     * @JMS\Groups({"logs"})
     */
    protected $externalSystemName;

    /**
     * @return string
     */
    public function getExternalSystemName()
    {
        return $this->externalSystemName;
    }

    /**
     * @param string $externalSystemName
     */
    public function setExternalSystemName($externalSystemName)
    {
        $this->externalSystemName = $externalSystemName;
    }
}
