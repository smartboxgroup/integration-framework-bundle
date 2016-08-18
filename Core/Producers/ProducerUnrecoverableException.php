<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Producers;

use Smartbox\Integration\FrameworkBundle\Exceptions\UnrecoverableExceptionInterface;

/**
 * Class ProducerUnrecoverableException.
 */
class ProducerUnrecoverableException extends \Exception implements UnrecoverableExceptionInterface
{
    public function __construct($message = 'Unrecoverable error in producer', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
