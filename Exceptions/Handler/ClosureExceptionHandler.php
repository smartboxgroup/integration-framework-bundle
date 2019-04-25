<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;

final class ClosureExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var \Closure
     */
    private $closure;

    /**
     * ClosureExceptionHandler constructor.
     * @param \Closure $closure
     */
    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * @param \Exception $exception
     * @return void
     */
    public function __invoke(\Exception $exception)
    {
        ($this->closure)($exception);
    }
}