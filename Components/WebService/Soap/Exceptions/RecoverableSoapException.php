<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Soap\Exceptions;

use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface;

/**
 * Class RecoverableSoapException.
 */
class RecoverableSoapException extends SoapException implements RecoverableExceptionInterface
{
}
