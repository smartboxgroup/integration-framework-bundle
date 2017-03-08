<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Producers;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface;

/**
 * Class ProducerRecoverableException.
 */
class ProducerRecoverableException extends \Exception implements RecoverableExceptionInterface, SerializableInterface
{
    use HasInternalType;

    const DEFAULT_MESSAGE= 'Recoverable error in producer';

    public function __construct($message = self::DEFAULT_MESSAGE, $code = 0, \Exception $previous = null)
    {
        if ($message == "") {
            $message = self::DEFAULT_MESSAGE;
        }
        parent::__construct($message, $code, $previous);
    }
}
