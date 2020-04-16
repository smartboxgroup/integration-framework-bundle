<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Core\Handlers\DecodingExceptionHandlerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\ReThrowExceptionHandler;

trait UsesDecodingExceptionHandler
{
    private $decodingExceptionHandler;

    /**
     * @return DecodingExceptionHandlerInterface
     */
    public function getDecodingExceptionHandler()
    {
        return $this->decodingExceptionHandler ?? new ReThrowExceptionHandler();
    }

    public function setDecodingExceptionHandler(DecodingExceptionHandlerInterface $decodingExceptionHandler)
    {
        $this->decodingExceptionHandler = $decodingExceptionHandler;
    }
}
