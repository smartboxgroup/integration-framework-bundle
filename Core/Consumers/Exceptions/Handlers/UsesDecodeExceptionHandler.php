<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Core\Consumers\Exceptions\Handlers;

trait UsesDecodeExceptionHandler
{
    private $decodeExceptionHandler;

    /**
     * @return DecodeExceptionHandlerInterface
     */
    public function getDecodeExceptionHandler()
    {
        return $this->decodeExceptionHandler ?? new ReThrowExceptionHandler();
    }

    /**
     * @return UsesDecodeExceptionHandler
     */
    public function setDecodeExceptionHandler(DecodeExceptionHandlerInterface $decodeExceptionHandler)
    {
        $this->decodeExceptionHandler = $decodeExceptionHandler;
    }
}
