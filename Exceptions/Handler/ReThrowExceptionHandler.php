<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;

final class ReThrowExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @param \Exception $exception
     *
     * @param mixed $context
     * @throws \Exception
     */
    public function __invoke(\Exception $exception, $context)
    {
        throw $exception;
    }
}
