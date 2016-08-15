<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Exception;

use Smartbox\Integration\FrameworkBundle\Components\WebService\HasExternalSystemName;
use Smartbox\Integration\FrameworkBundle\Components\WebService\HasShowExternalSystemErrorMessage;
use Smartbox\Integration\FrameworkBundle\Exceptions\UnrecoverableExceptionInterface;

/**
 * Class ExternalSystemAuthenticationException.
 */
class ExternalSystemAuthenticationException
    extends \RuntimeException
    implements UnrecoverableExceptionInterface, ExternalSystemExceptionInterface
{
    use HasExternalSystemName;
    use HasShowExternalSystemErrorMessage;
}
