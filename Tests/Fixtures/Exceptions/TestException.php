<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Exceptions;

/**
 * Class TestException.
 */
class TestException extends \Exception
{
    public function __construct($message = "This is a test exception")
    {
        parent::__construct($message);
    }
}
