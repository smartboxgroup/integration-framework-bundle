<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions;


class InvalidFormatException extends \Exception
{

    const ERROR_CODE = 701;
    const ERROR_MESSAGE = 'Format is not valid';

    public function __construct($message = "")
    {
        parent::__construct(self::ERROR_MESSAGE, self::ERROR_CODE);
    }
}