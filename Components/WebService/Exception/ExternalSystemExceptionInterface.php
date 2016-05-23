<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Exception;

/**
 * Interface ExternalSystemExceptionInterface
 */
interface ExternalSystemExceptionInterface
{
    /**
     * Get the name of the external system
     * @return string
     */
    public function getExternalSystemName();
}
