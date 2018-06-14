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
     * @var
     */
    protected $originalMessage;

    /**
     * @var
     */
    protected $originalCode;

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

    /**
     * @return mixed
     */
    public function getOriginalMessage()
    {
        return $this->originalMessage;
    }

    /**
     * @return mixed
     */
    public function getOriginalCode()
    {
        return $this->originalCode;
    }

    /**
     * @param mixed $originalMessage
     * @return HasShowExternalSystemErrorMessage
     */
    public function setOriginalMessage($originalMessage)
    {
        $this->originalMessage = $originalMessage;
        return $this;
    }

    /**
     * @param mixed $originalCode
     * @return HasShowExternalSystemErrorMessage
     */
    public function setOriginalCode($originalCode)
    {
        $this->originalCode = $originalCode;
        return $this;
    }
}
