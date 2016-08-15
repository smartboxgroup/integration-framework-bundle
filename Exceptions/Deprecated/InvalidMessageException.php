<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions\Deprecated;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\ExchangeAwareInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class InvalidMessageException.
 */
class InvalidMessageException extends \Exception implements ExchangeAwareInterface
{
    const ERROR_CODE = 701;

    /** @var MessageInterface */
    protected $invalidMessage;

    /** @var  \Smartbox\Integration\FrameworkBundle\Core\Exchange */
    protected $exchange;

    protected $errors = [];

    /**
     * @return MessageInterface
     */
    public function getInvalidMessage()
    {
        return $this->invalidMessage;
    }

    /**
     * @param MessageInterface $invalidMessage
     */
    public function setInvalidMessage($invalidMessage)
    {
        $this->invalidMessage = $invalidMessage;
    }

    /**
     * InvalidMessageException constructor.
     *
     * @param string                                $message
     * @param MessageInterface|null                 $invalidMessage
     * @param ConstraintViolationListInterface|null $errors
     */
    public function __construct(
        $message = '',
        MessageInterface $invalidMessage = null,
        ConstraintViolationListInterface $errors = null
    ) {
        $this->invalidMessage = $invalidMessage;
        $this->errors = $errors;
        parent::__construct($message, self::ERROR_CODE);
    }

    public function setErrors(ConstraintViolationListInterface $errors)
    {
        $this->errors = $errors;
    }

    /**
     * @param \Smartbox\Integration\FrameworkBundle\Core\Exchange $exchange
     *
     * @return mixed
     */
    public function setExchange(Exchange $exchange)
    {
        $this->invalidMessage = $exchange->getIn();
        $this->exchange = $exchange;
    }

    /**
     * @return \Smartbox\Integration\FrameworkBundle\Core\Exchange
     */
    public function getExchange()
    {
        return $this->exchange;
    }
}
