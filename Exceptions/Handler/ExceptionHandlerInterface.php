<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Handler;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;

interface ExceptionHandlerInterface
{
    /**
     * @param mixed $context
     */
    public function handle(\Exception $exception, $context);
}
