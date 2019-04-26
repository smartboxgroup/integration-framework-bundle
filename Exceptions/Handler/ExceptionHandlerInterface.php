<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;

interface ExceptionHandlerInterface
{
    /**
     * @param \Exception $e
     * @param mixed $context
     */
    public function __invoke(\Exception $e, $context);
}
