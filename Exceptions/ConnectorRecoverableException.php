<?php
namespace Smartbox\Integration\FrameworkBundle\Exceptions;


use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;

class ConnectorRecoverableException extends \Exception implements RecoverableExceptionInterface, SerializableInterface {

    use HasInternalType;

}
