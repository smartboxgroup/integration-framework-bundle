<?php


namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;


trait UsesExceptionHandlerTrait
{
    protected $exceptionHandler;

    /**
     * @return mixed
     */
    public function getExceptionHandler()
    {
        if (null === $this->exceptionHandler) {
            $this->exceptionHandler = new ReThrowExceptionHandler;
        }

        return $this->exceptionHandler;
    }

    /**
     * @param ExceptionHandlerInterface $exceptionHandler
     * @return UsesExceptionHandlerTrait
     */
    public function setExceptionHandler(ExceptionHandlerInterface $exceptionHandler)
    {
        $this->exceptionHandler = $exceptionHandler;
        
        return $this;
    }
}
