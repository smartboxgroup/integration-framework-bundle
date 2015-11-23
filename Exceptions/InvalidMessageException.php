<?php


namespace Smartbox\Integration\FrameworkBundle\Exceptions;

use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidMessageException extends \Exception implements ExchangeAwareInterface
{
    const ERROR_CODE = 701;

    /** @var MessageInterface */
    protected $invalidMessage;

    /** @var  Exchange */
    protected $exchange;

    protected $errors = array();

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

    public function __construct($message = "", MessageInterface $invalidMessage = null, ConstraintViolationListInterface $errors = null)
    {
        $this->invalidMessage = $invalidMessage;
        $this->errors = $errors;
        parent::__construct($message, self::ERROR_CODE);
    }

    public function setErrors(ConstraintViolationListInterface $errors){
        $this->errors = $errors;
    }

    /**
     * @param Exchange $exchange
     * @return mixed
     */
    public function setExchange(Exchange $exchange)
    {
        $this->invalidMessage = $exchange->getIn();
        $this->exchange = $exchange;
    }

    /**
     * @return Exchange
     */
    public function getExchange(){
        return $this->exchange;
    }
}