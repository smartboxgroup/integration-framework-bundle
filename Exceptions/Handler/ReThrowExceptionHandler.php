<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;

final class ReThrowExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function __invoke(\Exception $e, EndpointInterface $endpoint, $context)
    {
        throw $exception;
    }
}
