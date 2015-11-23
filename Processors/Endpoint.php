<?php

namespace Smartbox\Integration\FrameworkBundle\Processors;

use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Traits\UsesConnectorsRouter;

/**
 * Class Endpoint
 * @package Smartbox\Integration\FrameworkBundle\Processors
 */
class Endpoint extends Processor
{
    const OPTION_RETRIES = 'retries';
    const OPTION_USERNAME = 'username';
    const OPTION_PASSWORD = 'password';

    use UsesConnectorsRouter;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"logs"})
     * @JMS\Expose
     */
    protected $uri;

    /**
     * @return string
     */
    public function getURI()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setURI($uri)
    {
        $this->uri = $uri;
    }


    protected function doProcess(Exchange $exchange)
    {
        $this->sendTo($exchange,$this->getURI());
    }
}
