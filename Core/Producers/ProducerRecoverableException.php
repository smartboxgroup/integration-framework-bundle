<?php
namespace Smartbox\Integration\FrameworkBundle\Core\Producers;


use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface;

class ProducerRecoverableException extends \Exception implements RecoverableExceptionInterface, SerializableInterface {
    use HasInternalType;
}
