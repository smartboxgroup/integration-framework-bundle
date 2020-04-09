<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Core\Consumers\Exceptions\Handlers;

use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

interface DecodeExceptionHandlerInterface
{
    /**
     * @param mixed $context
     *
     * @return MessageInterface|null
     */
    public function handle(\Exception $exception, $context);
}
