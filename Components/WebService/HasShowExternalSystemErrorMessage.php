<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService;

/**
 * Class HasShowExternalSystemErrorMessage
 */
trait HasShowExternalSystemErrorMessage
{
    /** @var bool */
    protected $showExternalSystemErrorMessage = false;

    /**
     * @return boolean
     */
    public function mustShowExternalSystemErrorMessage()
    {
        return $this->showExternalSystemErrorMessage;
    }

    /**
     * @param boolean $showExternalSystemErrorMessage
     */
    public function setShowExternalSystemErrorMessage($showExternalSystemErrorMessage)
    {
        $this->showExternalSystemErrorMessage = (bool) $showExternalSystemErrorMessage;
    }
}
