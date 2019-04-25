<?php

namespace Smartbox\Integration\FrameworkBundle\Exceptions;

use Throwable;

class DeserializationException extends \Exception
{
    /**
     * @var string the text that failed to be deserialized
     */
    private $body;

    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return DeserializationException
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }
}
