<?php


namespace Smartbox\Integration\FrameworkBundle\Exceptions\Deprecated;

use Exception;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\ExchangeAwareInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidMessageException extends \Exception implements ExchangeAwareInterface
{
    const ERROR_CODE = 701;

    /** @var MessageInterface */
    protected $invalidMessage;

    /** @var  \Smartbox\Integration\FrameworkBundle\Core\Exchange */
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
     * @param \Smartbox\Integration\FrameworkBundle\Core\Exchange $exchange
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
    public function getExchange(){
        return $this->exchange;
    }
}