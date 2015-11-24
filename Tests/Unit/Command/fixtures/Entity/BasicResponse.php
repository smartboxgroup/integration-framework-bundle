<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Command\fixtures\Entity;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class BasicResponse
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Command\fixtures\Entity
 */
class BasicResponse extends ApiEntity
{

    /**
     * Code describing the result of the operation
     *
     * @Assert\Type(type="integer")
     * @Assert\NotBlank
     * @JMS\Groups({"public"})
     * @JMS\Type("integer")
     * @JMS\Expose
     */
    protected $code;

    /**
     * Message describing the result of the operation
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @JMS\Groups({"public"})
     * @JMS\Type("string")
     * @JMS\Expose
     */
    protected $message;

    function __construct($code = null, $message = null)
    {
        parent::__construct();
        $this->setCode($code);
        $this->setMessage($message);
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode($code)
    {
        if (!empty($code) && !is_numeric($code)) {
            throw new \InvalidArgumentException("Expected null or numeric value in method setCode");
        }

        $this->code = $code;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        if (!empty($message) && !is_string($message)) {
            throw new \InvalidArgumentException("Expected null or string in method setMessage");
        }

        $this->message = $message;
    }
}
