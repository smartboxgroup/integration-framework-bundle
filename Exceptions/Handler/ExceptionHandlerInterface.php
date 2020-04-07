<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;

interface ExceptionHandlerInterface
{
    /**
     * @param \Exception $e
     * @param EndpointInterface $endpoint
     * @param mixed $context
     */
    public function __invoke(\Exception $e, EndpointInterface $endpoint, $context);
}
