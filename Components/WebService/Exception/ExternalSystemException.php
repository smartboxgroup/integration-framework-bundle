<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Exception;

use Smartbox\Integration\FrameworkBundle\Components\WebService\HasExternalSystemName;

/**
 * Class ExternalSystemException
 */
class ExternalSystemException extends \Exception implements ExternalSystemExceptionInterface
{
    const EXCEPTION_MESSAGE_TEMPLATE = 'Target system "%s" failed to process the request';

    use HasExternalSystemName;

    /** @var ExternalSystemExceptionInterface */
    protected $originalException;

    /**
     * @param ExternalSystemExceptionInterface $originalException
     *
     * @return $this
     */
    public static function createFromException(ExternalSystemExceptionInterface $originalException)
    {
        $exception = new self(
            sprintf(self::EXCEPTION_MESSAGE_TEMPLATE, $originalException->getExternalSystemName())
        );
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
