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
     *
     * @param \Closure $closure
     */
    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * @param \Exception $exception
     * @param mixed $context
     */
    public function __invoke(\Exception $exception, $context)
    {
        ($this->closure)($exception, $context);
    }
}
