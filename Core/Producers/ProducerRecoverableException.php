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
    public function __construct($message = 'Recoverable error in producer', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
