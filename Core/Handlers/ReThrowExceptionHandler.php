<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Core\Handlers;

class ReThrowExceptionHandler implements DecodingExceptionHandlerInterface
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
