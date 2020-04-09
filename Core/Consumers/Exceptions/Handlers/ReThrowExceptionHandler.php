<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Core\Consumers\Exceptions\Handlers;

class ReThrowExceptionHandler implements DecodeExceptionHandlerInterface
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
