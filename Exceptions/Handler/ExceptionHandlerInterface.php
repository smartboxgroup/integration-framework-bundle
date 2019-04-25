<?php


namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;


interface ExceptionHandlerInterface
{
    /**
     * @param \Exception $e
     * @return void
     */
    public function __invoke(\Exception $e);
}