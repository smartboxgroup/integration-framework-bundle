<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService;

/**
 * Class HasShowExternalSystemErrorMessage.
 */
trait HasShowExternalSystemErrorMessage
{
    /** @var bool */
    protected $showExternalSystemErrorMessage = false;

    /**
     * @return bool
     */
    public function mustShowExternalSystemErrorMessage()
    {
        return $this->showExternalSystemErrorMessage;
    }

    /**
     * @param bool $showExternalSystemErrorMessage
     */
    public function setShowExternalSystemErrorMessage($showExternalSystemErrorMessage)
    {
        $this->showExternalSystemErrorMessage = (bool) $showExternalSystemErrorMessage;
    }
}
