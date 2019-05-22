<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Exception;

use Smartbox\Integration\FrameworkBundle\Components\WebService\HasExternalSystemName;
use Smartbox\Integration\FrameworkBundle\Components\WebService\HasShowExternalSystemErrorMessage;

/**
 * Class ExternalSystemException.
 */
class ExternalSystemException extends \Exception implements ExternalSystemExceptionInterface
{
    const EXCEPTION_MESSAGE_TEMPLATE = 'Target system "%s" failed to process the request';
    const EXTERNAL_EXCEPTION_CODE = 520;

    use HasExternalSystemName;
    use HasShowExternalSystemErrorMessage;

    /** @var ExternalSystemExceptionInterface */
    protected $originalException;

    /**
     * @param ExternalSystemExceptionInterface|\Exception $originalException
     *
     * @return $this
     */
    public static function createFromException(ExternalSystemExceptionInterface $originalException)
    {
        $message = sprintf(self::EXCEPTION_MESSAGE_TEMPLATE, $originalException->getExternalSystemName());
        $code = self::EXTERNAL_EXCEPTION_CODE;
        if ($originalException->mustShowExternalSystemErrorMessage()) {
            $message = $originalException->getOriginalMessage();
            $code = (int) $originalException->getOriginalCode();
        }

        $exception = new self($message, $code);

        $exception->setOriginalException($originalException);

        return $exception;
    }

    /**
     * @return ExternalSystemExceptionInterface
     */
    public function getOriginalException()
    {
        return $this->originalException;
    }

    /**
     * @param ExternalSystemExceptionInterface $originalException
     */
    public function setOriginalException(ExternalSystemExceptionInterface $originalException)
    {
        $this->originalException = $originalException;
    }
}
