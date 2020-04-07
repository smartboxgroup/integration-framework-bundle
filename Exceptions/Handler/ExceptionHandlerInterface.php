<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;

interface ExceptionHandlerInterface
{
    /**
     * @param mixed $context
     */
    public function handle(\Exception $exception, $context);
}
