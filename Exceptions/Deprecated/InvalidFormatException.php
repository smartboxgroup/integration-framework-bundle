<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Deprecated;

/**
 * Class InvalidFormatException.
 */
class InvalidFormatException extends \Exception
{
    const ERROR_CODE = 701;
    const ERROR_MESSAGE = 'Format is not valid';

    /**
     * InvalidFormatException constructor.
     *
     * @param string $message
     */
    public function __construct($message = '')
    {
        parent::__construct(self::ERROR_MESSAGE, self::ERROR_CODE);
    }
}
