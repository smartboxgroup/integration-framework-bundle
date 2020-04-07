<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;

interface ExceptionHandlerInterface
{
    /**
     * @param \Exception $exception
     * @param EndpointInterface $endpoint
     * @param mixed $context
     */
    public function __invoke(\Exception $exception, EndpointInterface $endpoint, $context);
}
