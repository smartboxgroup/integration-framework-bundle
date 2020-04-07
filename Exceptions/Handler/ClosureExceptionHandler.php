<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;

final class ClosureExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var \Closure
     */
    private $closure;

    /**
     * ClosureExceptionHandler constructor.
     */
    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(\Exception $exception, EndpointInterface $endpoint, $context)
    {
        ($this->closure)($exception, $endpoint, $context);
    }
}
