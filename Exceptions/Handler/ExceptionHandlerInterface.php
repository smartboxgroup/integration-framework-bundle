<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;

interface ExceptionHandlerInterface
{
    /**
     * @param \Exception $e
     */
    public function __invoke(\Exception $e);
}
