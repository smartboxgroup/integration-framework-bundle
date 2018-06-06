<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Exception;

/**
 * Interface ExternalSystemExceptionInterface.
 */
interface ExternalSystemExceptionInterface
{
    /**
     * Get the name of the external system.
     *
     * @return string
     */
    public function getExternalSystemName();

    /**
     * If returns true, the message of the exception will be displayed to the user.
     *
     * @return bool
     */
    public function mustShowExternalSystemErrorMessage();

    /**
     * @return mixed
     */
    public function getOriginalMessage();

    /**
     * @return mixed
     */
    public function getOriginalCode();
}
