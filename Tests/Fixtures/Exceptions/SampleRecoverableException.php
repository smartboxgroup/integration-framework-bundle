<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Exceptions;

use Smartbox\Integration\FrameworkBundle\Exceptions\ExchangeAwareInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\ExchangeAwareTrait;
use Smartbox\Integration\FrameworkBundle\Exceptions\RecoverableExceptionInterface;

class SampleRecoverableException extends \Exception implements ExchangeAwareInterface, RecoverableExceptionInterface {
    use ExchangeAwareTrait;
}