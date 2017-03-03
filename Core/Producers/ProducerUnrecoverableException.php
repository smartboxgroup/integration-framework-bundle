<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Producers;

use Smartbox\Integration\FrameworkBundle\Exceptions\UnrecoverableExceptionInterface;

/**
 * Class ProducerUnrecoverableException.
 */
class ProducerUnrecoverableException extends \Exception implements UnrecoverableExceptionInterface
{
    const DEFAULT_MESSAGE= 'Unrecoverable error in producer';

    public function __construct($message = self::DEFAULT_MESSAGE, $code = 0, \Exception $previous = null)
    {
        if ($message == "") {
            $message = self::DEFAULT_MESSAGE;
        }
        parent::__construct($message, $code, $previous);
    }
}
