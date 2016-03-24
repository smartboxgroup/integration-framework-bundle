<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Deprecated;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class BadRequestException.
 */
class BadRequestException extends BadRequestHttpException
{
    const ERROR_CODE = 801;
    const ERROR_MESSAGE = 'Bad request';

    /**
     * BadRequestException constructor.
     *
     * @param null|string $message
     */
    public function __construct($message)
    {
        parent::__construct(self::ERROR_MESSAGE, null, self::ERROR_CODE);
    }
}
