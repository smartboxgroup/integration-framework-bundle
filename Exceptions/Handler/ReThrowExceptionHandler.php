<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;

class ReThrowExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function handle(\Exception $exception, $context)
    {
        throw $exception;
    }
}
