<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Exceptions;

use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface;

class SampleRecoverableException extends \Exception implements \Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\ExchangeAwareInterface, RecoverableExceptionInterface
{
    use \Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\ExchangeAwareTrait;
}
