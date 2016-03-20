<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions;

use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface;

/**
 * Class UnrecoverableRestException
 *
 * @package \Smartbox\Integration\FrameworkBundle\Exceptions
 */
class UnrecoverableRestException extends RestException implements RecoverableExceptionInterface
{

}
