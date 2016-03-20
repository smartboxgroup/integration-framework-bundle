<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Soap\Exceptions;

use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface;

/**
 * Class RecoverableSoapException
 *
 * @package \Smartbox\Integration\FrameworkBundle\Exceptions
 */
class RecoverableSoapException extends SoapException implements RecoverableExceptionInterface
{

}
