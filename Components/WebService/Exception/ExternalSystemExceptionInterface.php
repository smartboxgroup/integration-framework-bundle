<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Exception;

/**
 * Interface ExternalSystemExceptionInterface
 */
interface ExternalSystemExceptionInterface
{
    const STATUS_CODE = 520;
    const STATUS_MESSAGE = 'External system failure';
    const EXCEPTION_MESSAGE_TEMPLATE = 'Target system "%s" failed to process the request';

    /**
     * Get the name of the external system
     * @return string
     */
    public function getExternalSystemName();
}
