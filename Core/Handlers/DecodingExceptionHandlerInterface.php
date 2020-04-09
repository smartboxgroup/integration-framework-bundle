<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Core\Handlers;

use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

interface DecodingExceptionHandlerInterface
{
    /**
     * @param mixed $context
     *
     * @return MessageInterface|null
     */
    public function handle(\Exception $exception, $context);
}
