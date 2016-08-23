<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Exceptions;

use Smartbox\Integration\FrameworkBundle\Exceptions\UnrecoverableExceptionInterface;

/**
 * Class ThrottlerLimitReachedException.
 */
class ThrottlingLimitReachedException extends \Exception implements UnrecoverableExceptionInterface
{
}
