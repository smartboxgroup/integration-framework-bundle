<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;

final class ReThrowExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @param \Exception $exception
     * @throws \Exception
     */
    public function __invoke(\Exception $exception)
    {
        throw $exception;
    }
}